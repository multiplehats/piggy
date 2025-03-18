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

        $this->logger = new Logger('giftcard-coupon-service');
    }

    /**
     * Initialize the service and register hooks.
     *
     * @return void
     */
    public function init(): void
    {
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

        // AJAX handler for checking gift card balance
        add_action('wp_ajax_leat_admin_check_giftcard_balance', [$this, 'ajax_check_giftcard_balance']);
        add_action('wp_ajax_nopriv_leat_check_giftcard_balance', [$this, 'ajax_check_giftcard_balance']);

        // Frontend AJAX handler for checking gift card balance
        add_action('wp_ajax_leat_check_giftcard_balance', [$this, 'ajax_check_frontend_giftcard_balance']);
        add_action('wp_ajax_nopriv_leat_check_giftcard_balance', [$this, 'ajax_check_frontend_giftcard_balance']);

        // Add gift card detection notes to the order when it's created
        add_action('woocommerce_checkout_order_created', [$this, 'add_giftcard_detection_notes_to_order'], 10, 1);

        // Add filter for gift card coupons in admin
        add_action('restrict_manage_posts', [$this, 'add_giftcard_filter_to_coupon_list']);
        add_filter('parse_query', [$this, 'filter_coupon_list_by_giftcard']);

        // Add scripts for gift card balance check in frontend
        // add_action('wp_enqueue_scripts', [$this, 'enqueue_giftcard_scripts']);

        // Register WooCommerce Blocks integration directly (since we're already being called from woocommerce_blocks_loaded)
        $this->register_blocks_integration();
    }

    /**
     * Register integration with WooCommerce Blocks
     */
    public function register_blocks_integration(): void
    {
        // Check if WooCommerce Blocks classes exist, using a class that should be available if Blocks is active
        if (!class_exists('Automattic\WooCommerce\Blocks\Package')) {
            return;
        }

        // Include our integration class
        $integration_class_path = dirname(dirname(dirname(__FILE__))) . '/Infrastructure/Blocks/GiftcardCouponIntegration.php';
        if (!file_exists($integration_class_path)) {
            $this->logger->error('GiftcardCouponIntegration class file not found', [
                'path' => $integration_class_path
            ]);
            return;
        }

        require_once $integration_class_path;

        // Register with Cart Block
        add_action(
            'woocommerce_blocks_cart_block_registration',
            function ($integration_registry) {
                $integration_registry->register(new \Leat\Infrastructure\Blocks\GiftcardCouponIntegration());
            }
        );

        // Register with Checkout Block
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function ($integration_registry) {
                $integration_registry->register(new \Leat\Infrastructure\Blocks\GiftcardCouponIntegration());
            }
        );
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


                wc_add_notice(
                    sprintf(
                        __('✅ Gift card %s detected and applied to your order.', 'leat-crm'),
                        $coupon_code
                    ),
                    'success'
                );


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

            // Create the coupon
            $coupon_data = [
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
        ]);

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
        ]);

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
        $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);
        $hash = $coupon->get_meta(WCCoupons::GIFTCARD_HASH);
        $program_uuid = $coupon->get_meta(WCCoupons::GIFTCARD_PROGRAM_UUID);
        $initial_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_INITIAL_BALANCE);
        $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
        $last_checked = (int) $coupon->get_meta(WCCoupons::GIFTCARD_LAST_CHECKED);

        // Add nonce for security
        wp_nonce_field('leat_giftcard_coupon_meta_box', 'leat_giftcard_coupon_meta_box_nonce');

?>
        <table class="form-table">
            <tr>
                <th><?php _e('Gift Card UUID', 'leat-crm'); ?></th>
                <td>
                    <input type="text" name="leat_giftcard_uuid" value="<?php echo esc_attr($uuid); ?>" class="regular-text" readonly />
                </td>
            </tr>
            <tr>
                <th><?php _e('Gift Card Hash', 'leat-crm'); ?></th>
                <td>
                    <input type="text" name="leat_giftcard_hash" value="<?php echo esc_attr($hash); ?>" class="regular-text" readonly />
                </td>
            </tr>
            <tr>
                <th><?php _e('Program UUID', 'leat-crm'); ?></th>
                <td>
                    <input type="text" name="leat_giftcard_program_uuid" value="<?php echo esc_attr($program_uuid); ?>" class="regular-text" readonly />
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
                    <p class="description">
                        <?php
                        printf(
                            __('Last checked: %s', 'leat-crm'),
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_checked)
                        );
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th></th>
                <td>
                    <button type="button" class="button" id="leat-check-giftcard-balance" data-uuid="<?php echo esc_attr($uuid); ?>">
                        <?php _e('Check balance', 'leat-crm'); ?>
                    </button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                    <span id="leat-giftcard-balance-result"></span>
                </td>
            </tr>
        </table>
        <script>
            jQuery(document).ready(function($) {
                $('#leat-check-giftcard-balance').on('click', function() {
                    var button = $(this);
                    var spinner = button.next('.spinner');
                    var result = $('#leat-giftcard-balance-result');
                    var uuid = button.data('uuid');

                    button.prop('disabled', true);
                    spinner.css('visibility', 'visible');
                    result.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'leat_admin_check_giftcard_balance',
                            uuid: uuid,
                            nonce: '<?php echo wp_create_nonce('leat_admin_check_giftcard_balance'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                result.html('<span style="color: green;">' + response.data.message + '</span>');
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                result.html('<span style="color: red;">' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            result.html('<span style="color: red;"><?php _e('Error checking balance', 'leat-crm'); ?></span>');
                        },
                        complete: function() {
                            button.prop('disabled', false);
                            spinner.css('visibility', 'hidden');
                        }
                    });
                });
            });
        </script>
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
     * AJAX handler for checking gift card balance.
     *
     * @return void
     */
    public function ajax_check_giftcard_balance(): void
    {
        if (!check_ajax_referer('leat_admin_check_giftcard_balance', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Invalid nonce.', 'leat-crm')
            ]);
            return;
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'leat-crm')
            ]);
            return;
        }

        $uuid = sanitize_text_field($_POST['uuid']);

        try {
            // Find all coupons
            $args = [
                'post_type' => 'shop_coupon',
                'post_status' => 'any',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => WCCoupons::GIFTCARD_UUID,
                        'value' => $uuid,
                        'compare' => '='
                    ]
                ]
            ];

            $query = new \WP_Query($args);

            if (!$query->have_posts()) {
                wp_send_json_error([
                    'message' => __('Gift card coupon not found.', 'leat-crm')
                ]);
                return;
            }

            $post = $query->posts[0];
            $coupon = new \WC_Coupon($post->ID);

            // Get the hash from the coupon
            $hash = $coupon->get_meta(WCCoupons::GIFTCARD_HASH);

            if (empty($hash)) {
                wp_send_json_error([
                    'message' => __('Gift card hash not found.', 'leat-crm')
                ]);
                return;
            }

            // Get the gift card from Leat
            $giftcard = $this->leatGiftcardRepository->find_by_hash($hash);

            if (!$giftcard) {
                wp_send_json_error([
                    'message' => __('Gift card not found in Leat.', 'leat-crm')
                ]);
                return;
            }

            // Check the balance
            $balance = $this->check_giftcard_balance($giftcard);

            if ($balance === null) {
                wp_send_json_error([
                    'message' => __('Failed to check gift card balance.', 'leat-crm')
                ]);
                return;
            }

            // Update the coupon with the new balance
            $this->repository->update_balance($coupon, $balance);

            wp_send_json_success([
                'balance' => wc_price($balance / 100),
                'balance_raw' => $balance,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error checking gift card balance via AJAX', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            wp_send_json_error([
                'message' => __('An error occurred while checking the gift card balance.', 'leat-crm'),
                'error' => $e->getMessage()
            ]);
        }
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
     * Enqueue scripts for gift card functionality in the frontend.
     *
     * @return void
     */
    public function enqueue_giftcard_scripts(): void
    {
        if (!is_cart() && !is_checkout()) {
            return;
        }

        // Add inline styles for both classic and block checkout
        wp_add_inline_style('woocommerce-inline', '
            .leat-giftcard-balance {
                margin-top: 5px;
                font-size: 0.9em;
                display: none;
            }
            .leat-giftcard-balance.success {
                color: green;
            }
            .leat-giftcard-balance.error {
                color: red;
            }
            .wc-block-components-totals-coupon__content .leat-giftcard-balance {
                margin-top: 0;
                margin-bottom: 8px;
            }
        ');

        // Add inline jQuery script for classic checkout
        wp_enqueue_script('jquery');

        $nonce = wp_create_nonce('leat_check_giftcard_balance');
        $ajax_url = admin_url('admin-ajax.php');
        $checking_text = __('Checking gift card balance...', 'leat-crm');
        $balance_text = __('Gift card balance: ', 'leat-crm');
        $error_text = __('Not a valid gift card or error checking balance.', 'leat-crm');

        $script = "
        jQuery(document).ready(function($) {
            // Create balance display element if it doesn't exist yet
            if ($('.leat-giftcard-balance').length === 0) {
                $('form.checkout_coupon, form.woocommerce-form-coupon').append('<div class=\"leat-giftcard-balance\"></div>');
            }

            // Cache the balance element
            var balanceEl = $('.leat-giftcard-balance');

            // Function to check gift card balance
            function checkGiftcardBalance(couponCode) {
                balanceEl.removeClass('success error').text('{$checking_text}').show();

                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        action: 'leat_check_giftcard_balance',
                        coupon_code: couponCode,
                        nonce: '{$nonce}'
                    },
                    success: function(response) {
                        if (response.success && response.data.is_giftcard) {
                            balanceEl.html('{$balance_text} <strong>' + response.data.balance + '</strong>').addClass('success').show();
                        } else {
                            balanceEl.hide();
                        }
                    },
                    error: function() {
                        balanceEl.hide();
                    }
                });
            }

            // Check on input change with debounce
            var timer;
            $('input[name=\"coupon_code\"]').on('input', function() {
                var couponCode = $(this).val().trim();
                clearTimeout(timer);

                // Hide balance display if input is empty
                if (couponCode === '') {
                    balanceEl.hide();
                    return;
                }

                // Only proceed if coupon code is at least 9 characters (gift card length)
                if (couponCode.length >= 9) {
                    // Debounce the check to avoid too many requests
                    timer = setTimeout(function() {
                        checkGiftcardBalance(couponCode);
                    }, 500);
                }
            });

            // Also check on form submit
            $('form.checkout_coupon, form.woocommerce-form-coupon').on('submit', function() {
                var couponCode = $(this).find('input[name=\"coupon_code\"]').val().trim();
                if (couponCode.length >= 9) {
                    checkGiftcardBalance(couponCode);
                }
            });

            // Check any existing coupon codes
            $('.woocommerce-checkout-review-order td.product-name, .cart_totals .cart-discount').each(function() {
                var text = $(this).text();
                var couponMatch = text.match(/[A-Z0-9]{9}/);

                if (couponMatch && couponMatch[0]) {
                    var couponEl = $('<div class=\"leat-applied-giftcard-balance\"></div>');
                    $(this).append(couponEl);

                    $.ajax({
                        url: '{$ajax_url}',
                        type: 'POST',
                        data: {
                            action: 'leat_check_giftcard_balance',
                            coupon_code: couponMatch[0],
                            nonce: '{$nonce}'
                        },
                        success: function(response) {
                            if (response.success && response.data.is_giftcard) {
                                couponEl.html('<small>{$balance_text}' + response.data.balance + '</small>').addClass('success').show();
                            }
                        }
                    });
                }
            });
        });
        ";

        wp_add_inline_script('jquery', $script);
    }

    /**
     * AJAX handler for checking gift card balance from the frontend.
     *
     * @return void
     */
    public function ajax_check_frontend_giftcard_balance(): void
    {
        // Verify nonce
        if (!check_ajax_referer('leat_check_giftcard_balance', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'leat-crm')
            ]);
            return;
        }

        $coupon_code = sanitize_text_field($_POST['coupon_code']);

        if (empty($coupon_code)) {
            wp_send_json_error([
                'message' => __('No coupon code provided.', 'leat-crm')
            ]);
            return;
        }

        try {
            // Try to get the coupon
            try {
                $coupon = new \WC_Coupon($coupon_code);
            } catch (\Exception $e) {
                wp_send_json_error([
                    'message' => __('Coupon not found.', 'leat-crm'),
                    'is_giftcard' => false
                ]);
                return;
            }

            // Check if this is a gift card coupon
            if (!$this->repository->is_giftcard($coupon)) {
                wp_send_json_error([
                    'message' => __('Not a gift card.', 'leat-crm'),
                    'is_giftcard' => false
                ]);
                return;
            }

            // Try to get the gift card from Leat first
            $hash = $coupon->get_code();
            $giftcard = $this->leatGiftcardRepository->find_by_hash($hash);

            if ($giftcard) {
                // Check the balance
                $balance = $this->check_giftcard_balance($giftcard);

                if ($balance !== null) {
                    // Update the coupon with the new balance
                    $this->repository->update_balance($coupon, $balance);

                    wp_send_json_success([
                        'is_giftcard' => true,
                        'balance' => wc_price($balance / 100),
                        'balance_raw' => $balance,
                    ]);
                    return;
                }
            }

            // Fallback to the stored balance if we couldn't get it from Leat
            $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);

            wp_send_json_success([
                'is_giftcard' => true,
                'balance' => wc_price($current_balance / 100),
                'balance_raw' => $current_balance,
                'fallback' => true
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error checking gift card balance via frontend AJAX', [
                'coupon_code' => $coupon_code,
                'error' => $e->getMessage(),
            ]);

            wp_send_json_error([
                'message' => __('An error occurred while checking the gift card balance.', 'leat-crm'),
                'error' => $e->getMessage()
            ]);
        }
    }
}
