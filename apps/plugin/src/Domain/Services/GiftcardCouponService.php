<?php

namespace Leat\Domain\Services;

use Leat\Domain\Interfaces\WPGiftcardCouponRepositoryInterface;
use Leat\Domain\Interfaces\GiftcardCouponServiceInterface;
use Leat\Domain\Interfaces\LeatGiftcardRepositoryInterface;
use Leat\Infrastructure\Constants\WCCoupons;
use Leat\Infrastructure\Constants\WCOrders;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;
use Piggy\Api\Models\Giftcards\Giftcard;

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
        WPGiftcardCouponRepositoryInterface $repository,
        LeatGiftcardRepositoryInterface $leatGiftcardRepository
    ) {
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
        add_action('woocommerce_order_status_completed', [$this, 'update_giftcard_balance_after_order']);

        // Handle gift card coupon refunds
        add_action('woocommerce_order_refunded', [$this, 'handle_giftcard_coupon_refund'], 10, 2);

        // Add meta box to coupon admin page
        add_action('add_meta_boxes', [$this, 'register_giftcard_coupon_meta_box']);
        add_action('save_post_shop_coupon', [$this, 'save_giftcard_coupon_meta_box']);

        // Add custom column to coupons list
        add_filter('manage_edit-shop_coupon_columns', [$this, 'add_giftcard_coupon_column']);
        add_action('manage_shop_coupon_posts_custom_column', [$this, 'render_giftcard_coupon_column'], 10, 2);

        // AJAX handler for checking gift card balance
        add_action('wp_ajax_leat_admin_check_giftcard_balance', [$this, 'ajax_check_giftcard_balance']);
    }

    /**
     * Check if a coupon code is a gift card and create a coupon if needed.
     * This is called before the checkout and cart forms are displayed.
     *
     * @return void
     */
    public function maybe_create_giftcard_coupon_from_code(): void
    {
        // Get applied coupons from the cart
        $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];

        foreach ($applied_coupons as $coupon_code) {
            // Skip if not a potential gift card (9 chars)
            if (strlen($coupon_code) !== 9) {
                continue;
            }

            try {
                // Check if this is a gift card by hash in Leat
                $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

                if ($giftcard) {
                    // If it's a gift card in Leat, check if it exists as a coupon in WooCommerce
                    $existing_coupon = $this->repository->find_by_hash($coupon_code);

                    if (!$existing_coupon) {
                        // Only create a new coupon if it doesn't exist
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
        // Skip if not a potential gift card (9 chars) or if we're not applying the coupon
        if (strlen($coupon_code) !== 9 || !$apply) {
            return $apply;
        }

        try {
            // Check if this is a gift card by hash in Leat
            $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

            if ($giftcard) {
                // If it's a gift card in Leat, check if it exists as a coupon in WooCommerce
                $existing_coupon = $this->repository->find_by_hash($coupon_code);

                if (!$existing_coupon) {
                    // Only create a new coupon if it doesn't exist
                    $coupon = $this->create_giftcard_coupon($giftcard);
                    $this->logger->info('Created gift card coupon during application', [
                        'coupon_code' => $coupon_code,
                    ]);

                    // If we couldn't create the coupon, don't apply it
                    if (!$coupon) {
                        wc_add_notice(__('This gift card could not be applied.', 'leat-crm'), 'error');
                        return false;
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
            // Check if a coupon with this hash already exists
            $existing_coupon = $this->repository->find_by_hash($hash);
            if ($existing_coupon) {
                $this->logger->info('Gift card coupon already exists', [
                    'hash' => $hash,
                    'coupon_id' => $existing_coupon->get_id(),
                ], true);

                // Update the balance if needed
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
        // Only process if we're not already dealing with an error
        if (is_wp_error($response)) {
            return $response;
        }

        $route = $request->get_route();

        if ($route === '/wc/store/v1/cart/apply-coupon') {
            $coupon_code = $request->get_param('code');

            try {
                // First check if this is a gift card by hash in Leat
                $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon_code);

                if ($giftcard) {
                    // If it's a gift card in Leat, check if it exists as a coupon in WooCommerce
                    $existing_coupon = $this->repository->find_by_hash($coupon_code);

                    if (!$existing_coupon) {
                        // Only create a new coupon if it doesn't exist
                        $coupon = $this->create_giftcard_coupon($giftcard);

                        if (!$coupon) {
                            // If we couldn't create the coupon, return an error
                            return new \WP_Error(
                                'leat_giftcard_error',
                                __('This gift card could not be applied.', 'leat-crm'),
                                ['status' => 400]
                            );
                        }
                    } else {
                        $coupon = $existing_coupon;
                    }

                    // Now validate the gift card
                    $is_valid = $this->validate_giftcard($coupon);

                    $this->logger->info('Gift card coupon validation result', [
                        'coupon_code' => $coupon_code,
                        'is_valid' => $is_valid,
                    ], true);

                    // If it's not valid, prevent it from being applied
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
     * Validate a gift card coupon before it's applied.
     *
     * @param bool $valid Whether the coupon is valid.
     * @param \WC_Coupon $coupon The coupon object.
     * @param \WC_Discounts $discounts The discounts object.
     * @return bool Whether the coupon is valid.
     */
    public function validate_giftcard_coupon(bool $valid, \WC_Coupon $coupon, \WC_Discounts $discounts): bool
    {
        // If the coupon is already invalid, return the original value
        if (!$valid) {
            return $valid;
        }

        // Only validate gift card coupons
        if (!$this->repository->is_giftcard($coupon)) {
            return $valid;
        }

        // Use the shared validation logic for gift cards
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

            // If no UUID, this is not a properly set up gift card coupon
            if (empty($uuid)) {
                $this->logger->info('Gift card coupon has no UUID', [
                    'coupon_code' => $coupon->get_code(),
                ], true);
                return false;
            }

            // Check when the balance was last checked
            $last_checked = (int) $coupon->get_meta(WCCoupons::GIFTCARD_LAST_CHECKED);
            $current_time = time();

            $balance = 0;

            // If the balance was checked recently, use the cached value
            if ($current_time - $last_checked < self::BALANCE_CHECK_CACHE_TIME) {
                $balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                $this->logger->info('Using cached gift card balance', [
                    'uuid' => $uuid,
                    'coupon_id' => $coupon->get_id(),
                    'balance' => $balance,
                ], true);
            } else {
                // Otherwise, check the balance from Leat
                // First get the Giftcard hash from the coupon code
                $hash = $coupon->get_code();

                // Then get the Giftcard object from Leat using the hash
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

            // If we couldn't get the balance, invalidate the coupon
            if ($balance === null) {
                $this->logger->error('Failed to validate gift card balance', [
                    'uuid' => $uuid,
                    'coupon_id' => $coupon->get_id(),
                ]);
                return false;
            }

            // If the balance is zero, invalidate the coupon
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

            // Log the balance for debugging
            $this->logger->info('Gift card balance check', [
                'uuid' => $giftcard->getUuid(),
                'hash' => $giftcard->getHash(),
                'balance' => $balance,
            ]);

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

        // Check if the order has already been processed for gift cards
        if ($order->get_meta(WCOrders::GIFT_CARD_PROCESSED)) {
            return;
        }

        // Get the coupons used in the order
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

                $discount_amount = $order->get_discount_total();
                $discount_amount_cents = (int) ($discount_amount * 100);

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
                                wc_price($discount_amount),
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
                                wc_price($discount_amount),
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
                            __('Gift card %s used: %s. Remaining balance: %s (calculated)', 'leat-crm'),
                            $coupon_code,
                            wc_price($discount_amount),
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
     * Handle gift card coupon refunds.
     *
     * @param int $order_id The order ID.
     * @param int $refund_id The refund ID.
     * @return void
     */
    public function handle_giftcard_coupon_refund(int $order_id, int $refund_id): void
    {
        $order = wc_get_order($order_id);
        $refund = wc_get_order($refund_id);

        if (!$order || !$refund) {
            return;
        }

        // Get the coupons used in the order
        $coupons = $order->get_coupon_codes();
        if (empty($coupons)) {
            return;
        }

        // Get the refund amount
        $refund_amount = $refund->get_amount();
        $order_total = $order->get_total();

        $refund_percentage = $refund_amount / $order_total;

        foreach ($coupons as $coupon_code) {
            try {
                $coupon = new \WC_Coupon($coupon_code);

                if (!$this->repository->is_giftcard($coupon)) {
                    continue;
                }

                $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);
                if (empty($uuid)) {
                    continue;
                }

                $transaction_id = $order->get_meta(WCOrders::GIFT_CARD_TRANSACTION_ID . $coupon_code);
                if (empty($transaction_id)) {
                    continue;
                }

                $discount_amount = $order->get_discount_total();

                // Calculate the refund amount for this gift card
                $refund_amount_for_giftcard = $discount_amount * $refund_percentage;
                $refund_amount_cents = (int) ($refund_amount_for_giftcard * 100);

                // Create a transaction in Leat to refund the amount
                $transaction = $this->leatGiftcardRepository->create_transaction($uuid, $refund_amount_cents);
                if (!$transaction) {
                    $this->logger->error('Failed to create gift card refund transaction', [
                        'uuid' => $uuid,
                        'order_id' => $order_id,
                        'refund_id' => $refund_id,
                        'amount' => $refund_amount_cents,
                    ]);
                    continue;
                }

                // Update the coupon balance
                $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                $new_balance = $current_balance + $refund_amount_cents;
                $this->repository->update_balance($coupon, $new_balance);

                // Add a note to the order
                OrderNotes::add_success(
                    $order,
                    sprintf(
                        __('Gift card %s refunded: %s. New balance: %s', 'leat-crm'),
                        $coupon_code,
                        wc_price($refund_amount_for_giftcard),
                        wc_price($new_balance / 100)
                    )
                );

                // Store the refund transaction ID in the order meta
                $order->add_meta_data(WCOrders::GIFT_CARD_REFUND_TRANSACTION_ID_PREFIX . $coupon_code, $transaction['uuid']);
                $order->save();
            } catch (\Exception $e) {
                $this->logger->error('Error processing gift card refund', [
                    'order_id' => $order_id,
                    'refund_id' => $refund_id,
                    'coupon_code' => $coupon_code,
                    'error' => $e->getMessage(),
                ]);
            }
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

            // Add our column after the coupon code column
            if ($key === 'coupon_code') {
                $new_columns['giftcard'] = __('Gift Card', 'leat-crm');
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
        // Check nonce
        if (!check_ajax_referer('leat_admin_check_giftcard_balance', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Invalid nonce.', 'leat-crm')
            ]);
            return;
        }

        // Check if user has permission
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
}
