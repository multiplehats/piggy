<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Interfaces\WPGiftcardCouponRepositoryInterface;
use Leat\Domain\Interfaces\GiftcardCouponServiceInterface;
use Leat\Infrastructure\Constants\WCCoupons;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;

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
     * API connection instance.
     *
     * @var Connection
     */
    private Connection $connection;

    /**
     * Gift card coupon repository instance.
     *
     * @var WPGiftcardCouponRepositoryInterface
     */
    private WPGiftcardCouponRepositoryInterface $repository;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Leat giftcard repository instance.
     *
     * @var LeatGiftcardRepositoryInterface
     */
    private LeatGiftcardRepositoryInterface $leatGiftcardRepository;


    /**
     * Balance check cache time in seconds (5 minutes).
     *
     * @var int
     */
    private const BALANCE_CHECK_CACHE_TIME = 300;

    /**
     * Constructor.
     *
     * @param Connection $connection API connection instance.
     * @param WPGiftcardCouponRepositoryInterface $repository Gift card coupon repository instance.
     * @param LeatGiftcardRepositoryInterface $leatGiftcardRepository Leat giftcard repository instance.
     */
    public function __construct(
        Connection $connection,
        WPGiftcardCouponRepositoryInterface $repository,
        LeatGiftcardRepositoryInterface $leatGiftcardRepository
    ) {
        $this->connection = $connection;
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
        // Validate gift card coupon before it's applied
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_giftcard_coupon'], 10, 3);

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
    }

    /**
     * Create a gift card coupon from a Leat gift card.
     *
     * @param array $giftcard The gift card data.
     * @return object|null The created coupon object or null on failure.
     */
    public function create_giftcard_coupon(array $giftcard): ?object
    {
        try {
            // Check if a coupon with this hash already exists
            $existing_coupon = $this->repository->find_by_hash($giftcard['hash']);
            if ($existing_coupon) {
                $this->logger->info('Gift card coupon already exists', [
                    'hash' => $giftcard['hash'],
                    'coupon_id' => $existing_coupon->get_id(),
                ]);
                return $existing_coupon;
            }

            // Get the current balance from Leat
            $balance = $this->check_giftcard_balance($giftcard['uuid']);
            if ($balance === null) {
                $this->logger->error('Failed to get gift card balance', [
                    'uuid' => $giftcard['uuid'],
                ]);
                return null;
            }

            // Create the coupon
            $coupon_data = [
                'uuid' => $giftcard['uuid'],
                'hash' => $giftcard['hash'],
                'program_uuid' => $giftcard['program_uuid'] ?? '',
                'balance' => $balance,
            ];

            return $this->repository->create($coupon_data);
        } catch (\Exception $e) {
            $this->logger->error('Error creating gift card coupon', [
                'giftcard' => $giftcard,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Validate a gift card coupon before it's applied.
     *
     * @param bool $valid Whether the coupon is valid.
     * @param \WC_Coupon $coupon The coupon object.
     * @param \WC_Discounts $discounts The discounts object.
     * @return bool Whether the coupon is valid.
     */
    public function validate_giftcard_coupon(bool $valid, \WC_Coupon $coupon,  \WC_Discounts $discounts): bool
    {
        try {
            // If the coupon is already invalid or not a gift card, return the original value
            if (!$valid) {
                return $valid;
            }

            // We need to check if the coupon code exists in Leat as a gift card.
            $giftcard = $this->leatGiftcardRepository->find_by_hash($coupon->get_code());

            if (!$giftcard) {
                // If it's not a gift card, we return early.

                return $valid;
            }

            // Get the gift card UUID
            $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);
            if (empty($uuid)) {
                // This means the gift card was created in Leat, but not in WooCommerce.
                // We need to create a new coupon in WooCommerce.
                $this->create_giftcard_coupon($giftcard);
            }

            // Check when the balance was last checked
            $last_checked = (int) $coupon->get_meta(WCCoupons::GIFTCARD_LAST_CHECKED);
            $current_time = time();

            // If the balance was checked recently, use the cached value
            if ($current_time - $last_checked < self::BALANCE_CHECK_CACHE_TIME) {
                $balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
            } else {
                // Otherwise, check the balance from Leat
                $balance = $this->check_giftcard_balance($uuid);

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
     * @param string $uuid The gift card UUID.
     * @return int|null The current balance in cents or null if not found.
     */
    public function check_giftcard_balance(string $uuid): ?int
    {
        try {
            // This is a placeholder for the actual API call
            // In a real implementation, you would call the Leat API to get the gift card balance
            // For example: $response = $this->connection->get_giftcard_balance($uuid);

            // For now, we'll simulate the API call by getting transactions
            $transactions = $this->get_giftcard_transactions($uuid);
            if ($transactions === null) {
                return null;
            }

            // Calculate the balance from transactions
            $balance = 0;
            foreach ($transactions as $transaction) {
                $balance += $transaction['amount_in_cents'];
            }

            return $balance;
        } catch (\Exception $e) {
            $this->logger->error('Error checking gift card balance', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get gift card transactions from Leat.
     *
     * @param string $uuid The gift card UUID.
     * @return array|null The transactions or null if not found.
     */
    private function get_giftcard_transactions(string $uuid): ?array
    {
        try {
            // This is a placeholder for the actual API call
            // In a real implementation, you would call the Leat API to get the gift card transactions
            // For example: $response = $this->connection->get_giftcard_transactions($uuid);

            // For now, we'll use a transient to simulate the API
            $transient_key = 'leat_giftcard_transactions_' . $uuid;
            $transactions = get_transient($transient_key);

            if ($transactions === false) {
                // Simulate API call
                $coupon = $this->repository->find_by_uuid($uuid);
                if (!$coupon) {
                    return null;
                }

                $initial_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_INITIAL_BALANCE);
                $transactions = [
                    [
                        'uuid' => wp_generate_uuid4(),
                        'amount_in_cents' => $initial_balance,
                        'type' => 1, // Credit
                        'created_at' => date('Y-m-d H:i:s', time() - 86400), // Yesterday
                    ]
                ];

                // Store in transient for 5 minutes
                set_transient($transient_key, $transactions, 300);
            }

            return $transactions;
        } catch (\Exception $e) {
            $this->logger->error('Error getting gift card transactions', [
                'uuid' => $uuid,
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
        if ($order->get_meta('_leat_giftcard_processed')) {
            return;
        }

        // Get the coupons used in the order
        $coupons = $order->get_coupon_codes();
        if (empty($coupons)) {
            return;
        }

        foreach ($coupons as $coupon_code) {
            try {
                $coupon = new \WC_Coupon($coupon_code);

                // Check if this is a gift card coupon
                if (!$this->repository->is_giftcard($coupon)) {
                    continue;
                }

                // Get the gift card UUID
                $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);
                if (empty($uuid)) {
                    continue;
                }

                // Get the discount amount applied
                $discount_amount = $order->get_discount_total();
                $discount_amount_cents = (int) ($discount_amount * 100);

                // Create a transaction in Leat to deduct the amount
                $transaction = $this->create_giftcard_transaction($uuid, -$discount_amount_cents);
                if (!$transaction) {
                    $this->logger->error('Failed to create gift card transaction', [
                        'uuid' => $uuid,
                        'order_id' => $order_id,
                        'amount' => -$discount_amount_cents,
                    ]);
                    continue;
                }

                // Update the coupon balance
                $current_balance = (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
                $new_balance = $current_balance - $discount_amount_cents;
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

                // Store the transaction ID in the order meta
                $order->add_meta_data('_leat_giftcard_transaction_' . $coupon_code, $transaction['uuid']);
            } catch (\Exception $e) {
                $this->logger->error('Error processing gift card after order', [
                    'order_id' => $order_id,
                    'coupon_code' => $coupon_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark the order as processed for gift cards
        $order->add_meta_data('_leat_giftcard_processed', true);
        $order->save();
    }

    /**
     * Create a gift card transaction in Leat.
     *
     * @param string $uuid The gift card UUID.
     * @param int $amount_in_cents The amount in cents.
     * @return array|null The transaction data or null on failure.
     */
    private function create_giftcard_transaction(string $uuid, int $amount_in_cents): ?array
    {
        try {
            // This is a placeholder for the actual API call
            // In a real implementation, you would call the Leat API to create a transaction
            // For example: $response = $this->connection->create_giftcard_transaction($uuid, $amount_in_cents);

            // For now, we'll simulate the API call
            $transient_key = 'leat_giftcard_transactions_' . $uuid;
            $transactions = get_transient($transient_key) ?: [];

            $transaction = [
                'uuid' => wp_generate_uuid4(),
                'amount_in_cents' => $amount_in_cents,
                'type' => $amount_in_cents > 0 ? 1 : 2, // 1 = Credit, 2 = Debit
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $transactions[] = $transaction;
            set_transient($transient_key, $transactions, 300);

            return $transaction;
        } catch (\Exception $e) {
            $this->logger->error('Error creating gift card transaction', [
                'uuid' => $uuid,
                'amount' => $amount_in_cents,
                'error' => $e->getMessage(),
            ]);
            return null;
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

        // Calculate the refund percentage
        $refund_percentage = $refund_amount / $order_total;

        foreach ($coupons as $coupon_code) {
            try {
                $coupon = new \WC_Coupon($coupon_code);

                // Check if this is a gift card coupon
                if (!$this->repository->is_giftcard($coupon)) {
                    continue;
                }

                // Get the gift card UUID
                $uuid = $coupon->get_meta(WCCoupons::GIFTCARD_UUID);
                if (empty($uuid)) {
                    continue;
                }

                // Get the original transaction ID
                $transaction_id = $order->get_meta('_leat_giftcard_transaction_' . $coupon_code);
                if (empty($transaction_id)) {
                    continue;
                }

                // Get the discount amount applied
                $discount_amount = $order->get_discount_total();

                // Calculate the refund amount for this gift card
                $refund_amount_for_giftcard = $discount_amount * $refund_percentage;
                $refund_amount_cents = (int) ($refund_amount_for_giftcard * 100);

                // Create a transaction in Leat to refund the amount
                $transaction = $this->create_giftcard_transaction($uuid, $refund_amount_cents);
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
                $order->add_meta_data('_leat_giftcard_refund_transaction_' . $coupon_code, $transaction['uuid']);
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
            __('Gift Card Information', 'leat-crm'),
            [$this, 'render_giftcard_coupon_meta_box'],
            'shop_coupon',
            'normal',
            'high'
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
            echo '<p>' . __('This is not a gift card coupon.', 'leat-crm') . '</p>';
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
                        <?php _e('Check Balance', 'leat-crm'); ?>
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
                            action: 'leat_check_giftcard_balance',
                            uuid: uuid,
                            nonce: '<?php echo wp_create_nonce('leat_check_giftcard_balance'); ?>'
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
}
