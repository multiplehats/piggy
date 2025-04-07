<?php

namespace Leat\Domain\Services;

use Leat\Domain\Interfaces\WPGiftcardCouponRepositoryInterface;
use Leat\Domain\Interfaces\GiftcardCouponServiceInterface;
use Leat\Domain\Interfaces\LeatGiftcardRepositoryInterface;
use Leat\Infrastructure\Constants\WCCoupons;
use Leat\Infrastructure\Constants\WCOrders;
use Leat\Settings;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;
use Leat\Utils\GiftcardDisplay;
use Piggy\Api\Models\Giftcards\Giftcard;
use WP_Query;

/**
 * Class GiftcardCouponService
 *
 * Service for handling gift card coupons.
 *
 * @package Leat\Domain\Services
 */
class GiftcardCouponService implements GiftcardCouponServiceInterface
{
    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Gift card coupon repository instance.
     *
     * @var WPGiftcardCouponRepositoryInterface
     */
    private $repository;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Leat giftcard repository instance.
     *
     * @var LeatGiftcardRepositoryInterface
     */
    private $leatGiftcardRepository;

    /**
     * Giftcard display utility instance.
     *
     * @var GiftcardDisplay
     */
    private $giftcardDisplay;

    /**
     * Balance check cache time in seconds (2 minutes).
     *
     * @var int
     */
    private const BALANCE_CHECK_CACHE_TIME = 120;

    /**
     * Constructor.
     *
     * @param WPGiftcardCouponRepositoryInterface $repository Gift card coupon repository instance.
     * @param LeatGiftcardRepositoryInterface $leatGiftcardRepository Leat giftcard repository instance.
     */
    public function __construct(
        Settings $settings,
        WPGiftcardCouponRepositoryInterface $repository,
        LeatGiftcardRepositoryInterface $leatGiftcardRepository,
    ) {
        $this->settings = $settings;
        $this->repository = $repository;
        $this->leatGiftcardRepository = $leatGiftcardRepository;
        $this->giftcardDisplay = new GiftcardDisplay($settings);

        $this->logger = new Logger('giftcard-coupon-service');
    }

    /**
     * Initialize the service and register hooks.
     *
     * @return void
     */
    public function init(): void
    {
        // Hook for the classic WooCommerce coupon system - runs before coupon existence is checked
        add_filter('woocommerce_get_shop_coupon_data', [$this, 'maybe_create_giftcard_coupon_on_get_data'], 10, 2);

        // Add hooks to display success message after coupon is applied
        add_action('woocommerce_applied_coupon', [$this, 'maybe_show_giftcard_success_message'], 10, 1);
        add_action('woocommerce_add_to_cart', [$this, 'show_giftcard_notices_on_cart_update'], 20);

        // Pre-emptive coupon creation for gift cards
        add_action('woocommerce_before_checkout_form', [$this, 'maybe_create_giftcard_coupon_from_code']);
        add_action('woocommerce_before_cart', [$this, 'maybe_create_giftcard_coupon_from_code']);

        // Add a filter to intercept coupon application
        add_filter('woocommerce_apply_coupon', [$this, 'maybe_create_giftcard_coupon_before_apply'], 10, 2);

        // Validate gift card coupon before it's applied (only for gift card coupons)
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_giftcard_coupon'], 10, 3);
        add_filter('rest_pre_dispatch', [$this, 'validate_giftcard_coupon_on_store_api'], 10, 3);

        // Update gift card balance after order is processed
        add_action('woocommerce_payment_complete', [$this, 'update_giftcard_balance_after_order']);

        $balance_update_order_statuses = $this->settings->get_setting_value_by_id('giftcard_coupon_balance_update_order_statuses') ?? ['refunded' => 'on'];
        foreach ($balance_update_order_statuses as $status => $enabled) {
            if ('on' === $enabled) {
                add_action('woocommerce_order_status_' . $status, [$this, 'update_giftcard_balance_after_order'], 10, 1);
            }
        }

        add_action('woocommerce_order_refunded', [$this, 'handle_giftcard_coupon_refund'], 10, 2);
        add_action('woocommerce_order_status_refunded', [$this, 'handle_giftcard_coupon_refund_by_status'], 10, 1);
        add_action('woocommerce_order_status_changed', [$this, 'handle_giftcard_coupon_refund_by_status_change'], 10, 3);
        add_action('woocommerce_order_fully_refunded', [$this, 'handle_giftcard_coupon_refund'], 10, 2);
        add_action('woocommerce_order_partially_refunded', [$this, 'handle_giftcard_coupon_refund'], 10, 2);

        // Add meta box to coupon admin page
        add_action('add_meta_boxes', [$this, 'register_giftcard_coupon_meta_box']);
        add_action('save_post_shop_coupon', [$this, 'save_giftcard_coupon_meta_box']);

        // Add custom column to coupons list
        add_filter('manage_edit-shop_coupon_columns', [$this, 'add_giftcard_coupon_column']);
        add_action('manage_shop_coupon_posts_custom_column', [$this, 'render_giftcard_coupon_column'], 10, 2);

        // Add gift card detection notes to the order when it's created
        add_action('woocommerce_checkout_order_created', [$this, 'add_giftcard_detection_notes_to_order'], 10, 1);

        // Add filter for gift card coupons in admin
        add_action('restrict_manage_posts', [$this, 'add_giftcard_filter_to_coupon_list']);
        add_filter('parse_query', [$this, 'filter_coupon_list_by_giftcard']);
    }

    /**
     * Check if a coupon code is a gift card and create a coupon if needed.
     * This is called before the checkout and cart forms are displayed.
     *
     * @return void
     */
    public function maybe_create_giftcard_coupon_from_code(): void
    {
        //
        $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];

        foreach ($applied_coupons as $coupon_code) {

            if (strlen($coupon_code) !== 9) {
                continue;
            }

            try {

                $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

                if ($giftcard) {

                    $existing_coupon = $this->repository->find_by_hash($coupon_code);

                    if (!$existing_coupon) {

                        $this->create_giftcard_coupon($giftcard);
                        $this->logger->info('Pre-emptively created gift card coupon', [
                            'coupon_code' => $coupon_code,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Error pre-emptively creating gift card coupon', [
                    'coupon_code' => $coupon_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Intercept coupon application to create gift card coupons if needed.
     * This is called when a coupon is being applied to the cart.
     *
     * @param bool $apply Whether to apply the coupon.
     * @param string $coupon_code The coupon code being applied.
     * @return bool Whether to apply the coupon.
     */
    public function maybe_create_giftcard_coupon_before_apply(bool $apply, string $coupon_code): bool
    {

        if (strlen($coupon_code) !== 9 || !$apply) {
            return $apply;
        }

        try {

            $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

            if ($giftcard) {

                $existing_coupon = $this->repository->find_by_hash($coupon_code);

                if (!$existing_coupon) {

                    $coupon = $this->create_giftcard_coupon($giftcard);
                    $this->logger->info('Created gift card coupon during application', [
                        'coupon_code' => $coupon_code,
                    ]);


                    if (!$coupon) {
                        wc_add_notice(__('This gift card could not be applied.', 'leat-crm'), 'error');
                        return false;
                    }
                }

                // We handle the success message in maybe_show_giftcard_success_message
                // to ensure consistent messaging across both checkout types

                if (WC()->session) {
                    $applied_giftcards = WC()->session->get('applied_giftcards', []);
                    if (!in_array($coupon_code, $applied_giftcards)) {
                        $applied_giftcards[] = $coupon_code;
                        WC()->session->set('applied_giftcards', $applied_giftcards);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error creating gift card coupon during application', [
                'coupon_code' => $coupon_code,
                'error' => $e->getMessage(),
            ]);
        }

        return $apply;
    }

    /**
     * Create a gift card coupon from a Leat gift card.
     *
     * @param Giftcard $giftcard The gift card data.
     * @return \WC_Coupon|null The created coupon object or null on failure.
     */
    public function create_giftcard_coupon(Giftcard $giftcard): ?\WC_Coupon
    {
        try {
            $hash = $giftcard->getHash();

            $existing_coupon = $this->repository->find_by_hash($hash);
            if ($existing_coupon) {
                $this->logger->info('Gift card coupon already exists', [
                    'hash' => $hash,
                    'coupon_id' => $existing_coupon->get_id(),
                ], true);


                $balance_in_cents = $this->check_giftcard_balance($giftcard);
                if ($balance_in_cents !== null) {
                    $this->repository->update_balance($existing_coupon, $balance_in_cents);
                }

                return $existing_coupon;
            }

            $balance_in_cents = $this->check_giftcard_balance($giftcard);

            if ($balance_in_cents === null) {
                $this->logger->error('Failed to get gift card balance', [
                    'uuid' => $giftcard->getUuid(),
                ], true);
                return null;
            }

            $coupon_data = [
                'id' => $giftcard->getId(),
                'uuid' => $giftcard->getUuid(),
                'hash' => $hash,
                'balance_in_cents' => $balance_in_cents,
                'program_uuid' => $giftcard->getGiftcardProgram()->getUuid(),
            ];

            $coupon = $this->repository->create($coupon_data);
            $this->logger->info('Gift card coupon created', [
                'coupon_id' => $coupon->get_id(),
            ], true);

            return $coupon;
        } catch (\Exception $e) {
            $this->logger->error('Error creating gift card coupon', [
                'giftcard' => $giftcard,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Handles coupon validation on the store API.
     *
     * @param mixed           $response The original response.
     * @param WP_REST_Server $server   REST API server instance.
     * @param WP_REST_Request $request  Request object.
     * @return mixed Modified response.
     */
    public function validate_giftcard_coupon_on_store_api($response, $server, $request)
    {

        if (is_wp_error($response)) {
            return $response;
        }

        $route = $request->get_route();

        if ($route === '/wc/store/v1/cart/apply-coupon') {
            $coupon_code = $request->get_param('code');

            try {

                $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

                if ($giftcard) {

                    $existing_coupon = $this->repository->find_by_hash($coupon_code);

                    if (!$existing_coupon) {

                        $coupon = $this->create_giftcard_coupon($giftcard);

                        if (!$coupon) {

                            return new \WP_Error(
                                'leat_giftcard_error',
                                __('This gift card could not be applied.', 'leat-crm'),
                                ['status' => 400]
                            );
                        }
                    } else {
                        $coupon = $existing_coupon;
                    }


                    $is_valid = $this->validate_giftcard($coupon);

                    $this->logger->info('Gift card coupon validation result', [
                        'coupon_code' => $coupon_code,
                        'is_valid' => $is_valid,
                    ], true);


                    if (!$is_valid) {
                        return new \WP_Error(
                            'leat_giftcard_invalid',
                            __('This gift card is invalid or has a zero balance.', 'leat-crm'),
                            ['status' => 400]
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Error validating gift card coupon on store API', [
                    'coupon_code' => $coupon_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }

    /**
     * Add a note to the order when a gift card is detected.
     *
     * @param string $coupon_code The coupon code.
     * @return void
     */
    private function add_giftcard_detected_note(string $coupon_code): void
    {

        if (!is_checkout() && !defined('WOOCOMMERCE_CHECKOUT')) {
            return;
        }


        $order_id = get_query_var('order-received', 0);
        if (!$order_id) {

            $order_id = WC()->session ? WC()->session->get('order_awaiting_payment') : 0;
        }

        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $note_added = $order->get_meta('_giftcard_detected_' . $coupon_code);
        if ($note_added) {
            return;
        }

        OrderNotes::add_success(
            $order,
            sprintf(
                __('✅ Gift card %s detected and applied to order.', 'leat-crm'),
                $coupon_code
            )
        );

        $order->update_meta_data('_giftcard_detected_' . $coupon_code, true);
        $order->save();
    }

    /**
     * Validate a gift card coupon before it's applied.
     *
     * @param bool $valid Whether the coupon is valid.
     * @param \WC_Coupon $coupon The coupon object.
     * @param \WC_Discounts $discounts The discounts object.
     * @return bool Whether the coupon is valid.
     */
    public function validate_giftcard_coupon(bool $valid, \WC_Coupon $coupon, \WC_Discounts $discounts): bool
    {
        if (!$valid) {
            return $valid;
        }

        if (!$this->repository->is_giftcard($coupon)) {
            return $valid;
        }

        $this->add_giftcard_detected_note($coupon->get_code());

        return $this->validate_giftcard($coupon);
    }

    /**
     * Shared validation logic for gift cards.
     *
     * @param \WC_Coupon $coupon The coupon object.
     * @return bool Whether the gift card is valid.
     */
    private function validate_giftcard(\WC_Coupon $coupon): bool
    {
        try {
            $this->logger->info('Validating gift card coupon', [
                'coupon_code' => $coupon->get_code(),
            ], true);

            $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);

            if (empty($uuid)) {
                $this->logger->info('Gift card coupon has no UUID', [
                    'coupon_code' => $coupon->get_code(),
                ], true);
                return false;
            }

            $last_checked = (int) $coupon->get_meta(WCCoupons::GIFTCARD_LAST_CHECKED);
            $current_time = time();

            $balance = 0;

            if ($current_time - $last_checked < self::BALANCE_CHECK_CACHE_TIME) {
                $balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                $this->logger->info('Using cached gift card balance', [
                    'uuid' => $uuid,
                    'coupon_id' => $coupon->get_id(),
                    'balance' => $balance,
                ], true);
            } else {

                $hash = $coupon->get_code();

                $giftcard = $this->leatGiftcardRepository->find_by_hash($hash);

                if (!$giftcard) {
                    $this->logger->error('Gift card not found in Leat', [
                        'uuid' => $uuid,
                        'hash' => $hash,
                        'coupon_id' => $coupon->get_id(),
                    ]);
                    return false;
                }

                $balance = $this->check_giftcard_balance($giftcard);

                $this->logger->info('Checking gift card balance from Leat', [
                    'uuid' => $uuid,
                    'coupon_id' => $coupon->get_id(),
                    'balance' => $balance,
                ], true);

                // Update the coupon with the new balance
                if ($balance !== null) {
                    $this->repository->update_balance($coupon, $balance);
                }
            }


            if ($balance === null) {
                $this->logger->error('Failed to validate gift card balance', [
                    'uuid' => $uuid,
                    'coupon_id' => $coupon->get_id(),
                ]);
                return false;
            }


            if ($balance <= 0) {
                $this->logger->info('Gift card has zero balance', [
                    'uuid' => $uuid,
                    'coupon_id' => $coupon->get_id(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $th) {
            $this->logger->error('Error validating gift card coupon', [
                'error' => $th->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check the balance of a gift card in Leat.
     *
     * @param Giftcard $giftcard The gift card object.
     * @return int|null The current balance in cents or null if not found.
     */
    public function check_giftcard_balance(Giftcard $giftcard): ?int
    {
        try {
            $balance = $giftcard->getAmountInCents();


            $this->logger->info('Gift card balance check', [
                'uuid' => $giftcard->getUuid(),
                'hash' => $giftcard->getHash(),
                'balance' => $balance,
            ], true);

            return $balance;
        } catch (\Exception $e) {
            $this->logger->error('Error checking gift card balance', [
                'uuid' => $giftcard->getUuid(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update the balance of a gift card coupon after an order is processed.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function update_giftcard_balance_after_order(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if ($order->get_meta(WCOrders::GIFT_CARD_PROCESSED)) {
            return;
        }

        $coupons = $order->get_coupon_codes();
        if (empty($coupons)) {
            return;
        }

        foreach ($coupons as $coupon_code) {
            try {
                // Try to get the coupon, but it might not exist anymore
                try {
                    $coupon = new \WC_Coupon($coupon_code);
                } catch (\Exception $e) {
                    $coupon = null;
                }

                // If the coupon doesn't exist or isn't a gift card, check if it's a gift card in Leat
                if (!$coupon || !$this->repository->is_giftcard($coupon)) {
                    // Check if this is a gift card in Leat by its code (hash)
                    $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

                    if (!$giftcard) {
                        // Not a gift card, skip
                        continue;
                    }

                    // It's a gift card in Leat but not in WooCommerce, create it
                    $coupon = $this->create_giftcard_coupon($giftcard);

                    if (!$coupon) {
                        $this->logger->error('Failed to create gift card coupon during order processing', [
                            'giftcard_hash' => $coupon_code,
                            'order_id' => $order_id,
                        ]);
                        continue;
                    }
                }

                // Get the gift card UUID
                $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);
                if (empty($uuid)) {
                    continue;
                }

                // Get the order total before discount
                $order_total_before_discount = $order->get_subtotal();
                $order_total_after_discount = $order->get_total();
                $actual_discount_used = $order_total_before_discount - $order_total_after_discount;

                // Convert to cents
                $discount_amount_cents = (int) ($actual_discount_used * 100);

                // Create a transaction for the actual amount used
                $transaction = $this->leatGiftcardRepository->create_transaction($uuid, -$discount_amount_cents);

                if (!$transaction) {
                    $this->logger->error('Failed to create gift card transaction', [
                        'uuid' => $uuid,
                        'order_id' => $order_id,
                        'amount' => -$discount_amount_cents,
                    ]);
                    continue;
                }

                // Get the latest balance from Leat to ensure we're in sync
                $hash = $coupon->get_meta(WCCoupons::GIFTCARD_HASH);
                $giftcard = $this->leatGiftcardRepository->find_by_hash($hash);

                if ($giftcard) {
                    $new_balance = $this->check_giftcard_balance($giftcard);
                    if ($new_balance !== null) {
                        $this->repository->update_balance($coupon, $new_balance);

                        // Add a note to the order
                        OrderNotes::add_success(
                            $order,
                            sprintf(
                                __('Gift card %s used: %s. Remaining balance: %s', 'leat-crm'),
                                $coupon_code,
                                wc_price($actual_discount_used),
                                wc_price($new_balance / 100)
                            )
                        );
                    } else {
                        // Fallback to calculating the balance if we can't get it from Leat
                        $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                        $new_balance = $current_balance - $discount_amount_cents;
                        $this->repository->update_balance($coupon, $new_balance);

                        // Add a note to the order
                        OrderNotes::add_success(
                            $order,
                            sprintf(
                                __('Gift card %s used: %s. Remaining balance: %s (calculated)', 'leat-crm'),
                                $coupon_code,
                                wc_price($actual_discount_used),
                                wc_price($new_balance / 100)
                            )
                        );
                    }
                } else {
                    // Fallback to calculating the balance if we can't get the gift card from Leat
                    $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                    $new_balance = $current_balance - $discount_amount_cents;
                    $this->repository->update_balance($coupon, $new_balance);

                    // Add a note to the order
                    OrderNotes::add_success(
                        $order,
                        sprintf(
                            __('No gift card found in Leat. Deducting balance from WooCommerce coupon %s used: %s. Remaining balance: %s (calculated)', 'leat-crm'),
                            $coupon_code,
                            wc_price($actual_discount_used),
                            wc_price($new_balance / 100)
                        )
                    );
                }

                // Store the transaction ID in the order meta
                $order->add_meta_data(WCOrders::GIFT_CARD_PROCESSED_TRANSACTION_ID_PREFIX . $coupon_code, $transaction->getUuid());
            } catch (\Exception $e) {
                $this->logger->error('Error processing gift card after order', [
                    'order_id' => $order_id,
                    'coupon_code' => $coupon_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark the order as processed for gift cards
        $order->add_meta_data(WCOrders::GIFT_CARD_PROCESSED, true);
        $order->save();
    }

    /**
     * Handle gift card coupon refunds when order status changes to refunded.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function handle_giftcard_coupon_refund_by_status(int $order_id): void
    {
        // Log that the refund handler is being called via status change
        $this->logger->info('Gift card refund handler called via status change', [
            'order_id' => $order_id,
        ], true);

        // Get refund IDs for this order
        $refunds = wc_get_orders([
            'type' => 'shop_order_refund',
            'parent' => $order_id,
            'limit' => 1,
        ]);

        if (!empty($refunds)) {
            $refund_id = $refunds[0]->get_id();
            $this->handle_giftcard_coupon_refund($order_id, $refund_id);
        } else {
            // No refund object found, handle without refund ID
            $this->handle_giftcard_coupon_refund_without_refund_id($order_id);
        }
    }

    /**
     * Handle gift card coupon refunds when order status changes.
     *
     * @param int $order_id The order ID.
     * @param string $from_status The previous status.
     * @param string $to_status The new status.
     * @return void
     */
    public function handle_giftcard_coupon_refund_by_status_change(int $order_id, string $from_status, string $to_status): void
    {
        if ($to_status === 'refunded') {
            $this->handle_giftcard_coupon_refund_by_status($order_id);
        }
    }

    /**
     * Handle gift card coupon refunds without a refund ID.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    private function handle_giftcard_coupon_refund_without_refund_id(int $order_id): void
    {
        $this->logger->info('Handling gift card refund without refund ID', [
            'order_id' => $order_id,
        ]);

        $order = wc_get_order($order_id);

        if (!$order) {
            $this->logger->error('Order not found', [
                'order_id' => $order_id,
            ]);
            return;
        }

        // Get the coupons used in the order
        $coupons = $order->get_coupon_codes();
        if (empty($coupons)) {
            $this->logger->info('No coupons found in order', [
                'order_id' => $order_id,
            ]);
            return;
        }

        $this->logger->info('Found coupons in order', [
            'order_id' => $order_id,
            'coupons' => $coupons,
        ]);

        // Full refund - refund 100% of the gift card amount
        $refund_percentage = 1.0;

        foreach ($coupons as $coupon_code) {
            $this->process_giftcard_refund($order, $coupon_code, $refund_percentage);
        }
    }

    /**
     * Handle gift card coupon refunds.
     *
     * @param int $order_id The order ID.
     * @param int $refund_id The refund ID.
     * @return void
     */
    public function handle_giftcard_coupon_refund(int $order_id, int $refund_id): void
    {
        // Log that the refund handler is being called
        $this->logger->info('Gift card refund handler called', [
            'order_id' => $order_id,
            'refund_id' => $refund_id,
        ], true);

        $order = wc_get_order($order_id);
        $refund = wc_get_order($refund_id);

        if (!$order || !$refund) {
            $this->logger->error('Order or refund not found', [
                'order_id' => $order_id,
                'refund_id' => $refund_id,
            ]);
            return;
        }

        // Get the coupons used in the order
        $coupons = $order->get_coupon_codes();
        if (empty($coupons)) {
            $this->logger->info('No coupons found in order', [
                'order_id' => $order_id,
            ]);
            return;
        }

        $this->logger->info('Found coupons in order', [
            'order_id' => $order_id,
            'coupons' => $coupons,
        ]);

        // Get the refund amount
        $refund_amount = $refund->get_amount();
        $order_total = $order->get_total();

        $refund_percentage = $refund_amount / $order_total;

        foreach ($coupons as $coupon_code) {
            $this->process_giftcard_refund($order, $coupon_code, $refund_percentage, $refund_id);
        }
    }

    /**
     * Process gift card refund for a specific coupon.
     *
     * @param \WC_Order $order The order object.
     * @param string $coupon_code The coupon code.
     * @param float $refund_percentage The refund percentage.
     * @param int|null $refund_id The refund ID (optional).
     * @return void
     */
    private function process_giftcard_refund(\WC_Order $order, string $coupon_code, float $refund_percentage, ?int $refund_id = null): void
    {
        try {
            $coupon = new \WC_Coupon($coupon_code);

            if (!$this->repository->is_giftcard($coupon)) {
                return;
            }

            $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);
            if (empty($uuid)) {
                return;
            }

            $transaction_id = $order->get_meta(WCOrders::GIFT_CARD_PROCESSED_TRANSACTION_ID_PREFIX . $coupon_code);
            if (empty($transaction_id)) {
                // Log that we couldn't find the transaction ID
                $this->logger->error('No transaction ID found for gift card coupon during refund', [
                    'order_id' => $order->get_id(),
                    'refund_id' => $refund_id,
                    'coupon_code' => $coupon_code,
                ]);
                return;
            }

            // Check if this transaction has already been refunded
            $refund_transaction_id = $order->get_meta(WCOrders::GIFT_CARD_REFUND_TRANSACTION_ID_PREFIX . $coupon_code);
            if (!empty($refund_transaction_id)) {
                $this->logger->info('Gift card transaction already refunded', [
                    'order_id' => $order->get_id(),
                    'refund_id' => $refund_id,
                    'coupon_code' => $coupon_code,
                    'transaction_id' => $transaction_id,
                    'refund_transaction_id' => $refund_transaction_id,
                ]);

                // Get the current balance from the gift card
                $hash = $coupon->get_meta(WCCoupons::GIFTCARD_HASH);
                $giftcard = $this->leatGiftcardRepository->find_by_hash($hash);

                if ($giftcard) {
                    $new_balance = $this->check_giftcard_balance($giftcard);
                    if ($new_balance !== null) {
                        $this->repository->update_balance($coupon, $new_balance);

                        // Add a note to the order about the already refunded transaction
                        OrderNotes::add_success(
                            $order,
                            sprintf(
                                __('Gift card %s was already refunded. Current balance: %s', 'leat-crm'),
                                $coupon_code,
                                wc_price($new_balance / 100)
                            )
                        );
                    }
                }

                return;
            }

            // Get the order total before discount and after discount
            $order_total_before_discount = $order->get_subtotal();
            $order_total_after_discount = $order->get_total();
            $actual_discount_used = $order_total_before_discount - $order_total_after_discount;

            // Calculate the refund amount for this gift card
            $refund_amount_for_giftcard = $actual_discount_used * $refund_percentage;
            $refund_amount_cents = (int) ($refund_amount_for_giftcard * 100);

            // Try to reverse the transaction
            try {
                $transaction = $this->leatGiftcardRepository->reverse_transaction($transaction_id);

                if (!$transaction) {
                    throw new \Exception('Failed to reverse transaction');
                }

                // Update the coupon balance
                $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                $new_balance = $current_balance + $refund_amount_cents;
                $this->repository->update_balance($coupon, $new_balance);

                // Add a note to the order
                OrderNotes::add_success(
                    $order,
                    sprintf(
                        __('Gift card %s refunded: %s. New balance: %s (Transaction reversed: %s)', 'leat-crm'),
                        $coupon_code,
                        wc_price($refund_amount_for_giftcard),
                        wc_price($new_balance / 100),
                        $transaction_id
                    )
                );

                // Store the refund transaction ID in the order meta
                if ($transaction && method_exists($transaction, 'getUuid')) {
                    $order->add_meta_data(WCOrders::GIFT_CARD_REFUND_TRANSACTION_ID_PREFIX . $coupon_code, $transaction->getUuid());
                    $order->save();
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to reverse gift card transaction, trying to create a new transaction', [
                    'uuid' => $uuid,
                    'order_id' => $order->get_id(),
                    'refund_id' => $refund_id,
                    'transaction_id' => $transaction_id,
                    'error' => $e->getMessage(),
                ]);

                // If reversing fails, try to create a new positive transaction instead
                $transaction = $this->leatGiftcardRepository->create_transaction($uuid, $refund_amount_cents);

                if (!$transaction) {
                    $this->logger->error('Failed to create gift card refund transaction', [
                        'uuid' => $uuid,
                        'order_id' => $order->get_id(),
                        'refund_id' => $refund_id,
                        'amount' => $refund_amount_cents,
                    ]);
                    return;
                }

                // Update the coupon balance
                $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                $new_balance = $current_balance + $refund_amount_cents;
                $this->repository->update_balance($coupon, $new_balance);

                // Add a note to the order
                OrderNotes::add_success(
                    $order,
                    sprintf(
                        __('Gift card %s refunded: %s. New balance: %s (New transaction created)', 'leat-crm'),
                        $coupon_code,
                        wc_price($refund_amount_for_giftcard),
                        wc_price($new_balance / 100)
                    )
                );

                // Store the refund transaction ID in the order meta
                if ($transaction && method_exists($transaction, 'getUuid')) {
                    $order->add_meta_data(WCOrders::GIFT_CARD_REFUND_TRANSACTION_ID_PREFIX . $coupon_code, $transaction->getUuid());
                    $order->save();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing gift card refund', [
                'order_id' => $order->get_id(),
                'refund_id' => $refund_id,
                'coupon_code' => $coupon_code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register gift card coupon meta box.
     *
     * @return void
     */
    public function register_giftcard_coupon_meta_box(): void
    {
        add_meta_box(
            'leat-giftcard-coupon',
            __('Leat - Gift Card Information', 'leat-crm'),
            [$this, 'render_giftcard_coupon_meta_box'],
            'shop_coupon',
            'normal',
            'low'
        );
    }

    /**
     * Render gift card coupon meta box.
     *
     * @param \WP_Post $post The post object.
     * @return void
     */
    public function render_giftcard_coupon_meta_box(\WP_Post $post): void
    {
        $coupon = new \WC_Coupon($post->ID);

        // Check if this is a gift card coupon
        if (!$this->repository->is_giftcard($coupon)) {
            echo '<p>' . __('This is not a gift card coupon, so the balance cannot be checked.', 'leat-crm') . '</p>';
            return;
        }

        // Get gift card data
        $id = $coupon->get_meta(WCCoupons::GIFTCARD_COUPON_ID);
        $hash = $coupon->get_meta(WCCoupons::GIFTCARD_HASH);
        $initial_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_INITIAL_BALANCE);
        $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);

        // Add nonce for security
        wp_nonce_field('leat_giftcard_coupon_meta_box', 'leat_giftcard_coupon_meta_box_nonce');

?>
        <table class="form-table">
            <tr>
                <th></th>
                <td>
                    <a href="<?php echo esc_url('https://business.leat.com/store/giftcards/program/cards?card_id=' . $id); ?>" target="_blank">
                        <?php _e('View card in Leat', 'leat-crm'); ?>
                    </a>
                </td>
            </tr>

            <tr>
                <th><?php _e('Gift Card code', 'leat-crm'); ?></th>
                <td>
                    <input type="text" name="leat_giftcard_hash" value="<?php echo esc_attr($hash); ?>" class="regular-text" readonly />
                </td>
            </tr>

            <tr>
                <th><?php _e('Initial Balance', 'leat-crm'); ?></th>
                <td>
                    <?php echo wc_price($initial_balance / 100); ?>
                </td>
            </tr>

            <tr>
                <th><?php _e('Current Balance', 'leat-crm'); ?></th>
                <td>
                    <?php echo wc_price($current_balance / 100); ?>
                </td>
            </tr>
        </table>
<?php
    }

    /**
     * Save gift card coupon meta box data.
     *
     * @param int $post_id The post ID.
     * @return void
     */
    public function save_giftcard_coupon_meta_box(int $post_id): void
    {
        // Check if our nonce is set
        if (!isset($_POST['leat_giftcard_coupon_meta_box_nonce'])) {
            return;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['leat_giftcard_coupon_meta_box_nonce'], 'leat_giftcard_coupon_meta_box')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Nothing to save for now, as all fields are read-only
    }

    /**
     * Add gift card coupon column to coupons list.
     *
     * @param array $columns The columns.
     * @return array The modified columns.
     */
    public function add_giftcard_coupon_column(array $columns): array
    {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ($key === 'coupon_code') {
                $new_columns['giftcard'] = __('Leat - Gift Card', 'leat-crm');
            }
        }

        return $new_columns;
    }

    /**
     * Render gift card coupon column.
     *
     * @param string $column The column name.
     * @param int $post_id The post ID.
     * @return void
     */
    public function render_giftcard_coupon_column(string $column, int $post_id): void
    {
        if ($column !== 'giftcard') {
            return;
        }

        $coupon = new \WC_Coupon($post_id);

        // Check if this is a gift card coupon
        if (!$this->repository->is_giftcard($coupon)) {
            echo '—';
            return;
        }

        // Get the current balance
        $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);

        echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . wc_price($current_balance / 100) . '</mark>';
    }

    /**
     * Add gift card detection notes to the order when it's created.
     *
     * @param \WC_Order $order The order object.
     * @return void
     */
    public function add_giftcard_detection_notes_to_order(\WC_Order $order): void
    {
        // Check if we have any applied gift cards in the session
        if (!WC()->session) {
            return;
        }

        $applied_giftcards = WC()->session->get('applied_giftcards', []);
        if (empty($applied_giftcards)) {
            return;
        }

        foreach ($applied_giftcards as $coupon_code) {
            // Add the note
            OrderNotes::add_success(
                $order,
                sprintf(
                    __('✅ Gift card %s detected and applied to order.', 'leat-crm'),
                    $coupon_code
                )
            );
        }

        // Clear the session data
        WC()->session->set('applied_giftcards', []);
    }

    /**
     * Adds a filter button to the coupon list page in admin
     *
     * @return void
     */
    public function add_giftcard_filter_to_coupon_list(): void
    {
        global $typenow;

        // Only on the coupon list page
        if ($typenow !== 'shop_coupon') {
            return;
        }

        $current = isset($_GET['leat_giftcards']) ? $_GET['leat_giftcards'] : '';

        echo '<select name="leat_giftcards" id="filter-by-leat-giftcards">';
        echo '<option value="">' . esc_html__('All coupons', 'leat') . '</option>';
        echo '<option value="true" ' . selected($current, 'true', false) . '>' .
            esc_html__('Gift card coupons only', 'leat') . '</option>';
        echo '</select>';
    }

    /**
     * Filters the coupon list query to show only gift card coupons
     *
     * @param WP_Query $query
     * @return WP_Query
     */
    public function filter_coupon_list_by_giftcard(WP_Query $query): WP_Query
    {
        global $pagenow, $typenow;

        // Only on the coupon list page when our filter is active
        if ($pagenow !== 'edit.php' || $typenow !== 'shop_coupon' || !isset($_GET['leat_giftcards']) || $_GET['leat_giftcards'] !== 'true') {
            return $query;
        }

        // Add meta query to only show gift card coupons
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key'     => WCCoupons::GIFTCARD_UUID,
            'compare' => 'EXISTS',
        ];

        $query->set('meta_query', $meta_query);

        return $query;
    }

    /**
     * Check if a coupon code is actually a gift card and create it if needed.
     * This runs when WooCommerce first checks if a coupon exists.
     *
     * @param mixed $data The coupon data (false if it doesn't exist yet)
     * @param string $coupon_code The coupon code being checked
     * @return mixed The coupon data or false
     */
    public function maybe_create_giftcard_coupon_on_get_data($data, string $coupon_code)
    {
        // If the coupon data is already found, return it
        if ($data !== false) {
            return $data;
        }

        // Check if this might be a gift card (hash is 9 characters)
        if (strlen($coupon_code) !== 9) {
            return $data;
        }

        try {
            // Check if this is a gift card in Leat
            $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

            if (!$giftcard) {
                // Not a gift card, return the original data
                return $data;
            }

            // Check if we already have a coupon for this gift card
            $existing_coupon = $this->repository->find_by_hash($coupon_code);

            if ($existing_coupon) {
                // Return coupon data instead of just true
                return [
                    'id' => $existing_coupon->get_id(),
                    'code' => $existing_coupon->get_code(),
                    'discount_type' => $existing_coupon->get_discount_type(),
                    'amount' => $existing_coupon->get_amount(),
                    'date_created' => $existing_coupon->get_date_created(),
                    'date_modified' => $existing_coupon->get_date_modified(),
                    'date_expires' => $existing_coupon->get_date_expires(),
                ];
            }

            // Create the gift card coupon
            $coupon = $this->create_giftcard_coupon($giftcard);

            if (!$coupon) {
                $this->logger->error('Failed to create gift card coupon during lookup', [
                    'coupon_code' => $coupon_code,
                ]);
                return $data;
            }

            $this->logger->info('Created gift card coupon during lookup', [
                'coupon_code' => $coupon_code,
                'coupon_id' => $coupon->get_id(),
            ]);

            // Track applied gift cards in session
            if (WC()->session) {
                $applied_giftcards = WC()->session->get('applied_giftcards', []);
                if (!in_array($coupon_code, $applied_giftcards)) {
                    $applied_giftcards[] = $coupon_code;
                    WC()->session->set('applied_giftcards', $applied_giftcards);
                }
            }

            // Return coupon data instead of just true
            return [
                'id' => $coupon->get_id(),
                'code' => $coupon->get_code(),
                'discount_type' => $coupon->get_discount_type(),
                'amount' => $coupon->get_amount(),
                'date_created' => $coupon->get_date_created(),
                'date_modified' => $coupon->get_date_modified(),
                'date_expires' => $coupon->get_date_expires(),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error checking/creating gift card coupon during lookup', [
                'coupon_code' => $coupon_code,
                'error' => $e->getMessage(),
            ]);

            return $data;
        }
    }

    /**
     * Add a hook to display success message after coupon is applied
     *
     * @param string $coupon_code The coupon code being applied
     * @return void
     */
    public function maybe_show_giftcard_success_message(string $coupon_code): void
    {

        $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

        if ($giftcard) {
            global $woocommerce;

            $balance_in_cents = $this->check_giftcard_balance($giftcard);
            $formatted_balance = ($balance_in_cents !== null) ? wc_price($balance_in_cents / 100) : __('N/A', 'leat-crm');

            // Use GiftcardDisplay to get the formatted message
            $message = $this->giftcardDisplay->get_formatted_success_message($coupon_code, $formatted_balance);

            // Add the message with high priority if it's not null
            if ($message) {
                wc_add_notice($message, 'success');
            }

            // Track in session
            if ($woocommerce && $woocommerce->session) {
                $applied_giftcards = $woocommerce->session->get('applied_giftcards', []);
                if (!in_array($coupon_code, $applied_giftcards)) {
                    $applied_giftcards[] = $coupon_code;
                    $woocommerce->session->set('applied_giftcards', $applied_giftcards);
                }
            }
        }
    }

    /**
     * Show gift card notices when cart is updated
     *
     * @return void
     */
    public function show_giftcard_notices_on_cart_update(): void
    {
        if (!WC()->cart) {
            return;
        }

        $applied_coupons = WC()->cart->get_applied_coupons();

        foreach ($applied_coupons as $coupon_code) {
            $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);
            if ($giftcard) {
                // Get the current balance
                $balance_in_cents = $this->check_giftcard_balance($giftcard);
                $formatted_balance = ($balance_in_cents !== null) ? wc_price($balance_in_cents / 100) : __('N/A', 'leat-crm');

                // Use GiftcardDisplay to get the formatted message
                $message = $this->giftcardDisplay->get_formatted_success_message($coupon_code, $formatted_balance);

                // Add the message with high priority if it's not null
                if ($message) {
                    wc_add_notice($message, 'success');
                }

                // Track in session
                if (WC()->session) {
                    $applied_giftcards = WC()->session->get('applied_giftcards', []);
                    if (!in_array($coupon_code, $applied_giftcards)) {
                        $applied_giftcards[] = $coupon_code;
                        WC()->session->set('applied_giftcards', $applied_giftcards);
                    }
                }
            }
        }
    }
}
