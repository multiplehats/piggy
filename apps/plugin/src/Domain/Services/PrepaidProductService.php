<?php

namespace Leat\Domain\Services;

use Leat\Domain\Interfaces\LeatPrepaidRepositoryInterface;
use Leat\Infrastructure\Repositories\WPPrepaidRepository;
use Leat\Infrastructure\Constants\WCOrders;
use Leat\Settings;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;
use WC_Order;
use WC_Product;
use WC_Order_Item_Product;
use Leat\Api\Connection;

/**
 * Class PrepaidProductService
 *
 * Service class for handling WooCommerce products that add prepaid balance.
 *
 * @package Leat\Domain\Services
 */
class PrepaidProductService
{
    private LeatPrepaidRepositoryInterface $leatRepository;
    private WPPrepaidRepository $wpRepository;
    private Settings $settings;
    private Logger $logger;
    private ApiService $apiService; // To get shop UUID
    private Connection $connection;

    public function __construct(
        LeatPrepaidRepositoryInterface $leatRepository,
        WPPrepaidRepository $wpRepository,
        Settings $settings,
        ApiService $apiService,
        Connection $connection
    ) {
        $this->leatRepository = $leatRepository;
        $this->wpRepository = $wpRepository;
        $this->settings = $settings;
        $this->apiService = $apiService;
        $this->connection = $connection;
        $this->logger = new Logger();
    }

    public function init(): void
    {
        // Add prepaid product setting checkbox.
        add_filter('woocommerce_product_data_tabs', [$this, 'add_prepaid_product_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'add_prepaid_product_settings']);
        add_action('woocommerce_process_product_meta', [$this, 'save_prepaid_product_settings']);

        // Process prepaid top-ups after order status changes.
        $trigger_status = $this->settings->get_setting_value_by_id('prepaid_order_status', 'completed'); // Default to completed
        add_action("woocommerce_order_status_{$trigger_status}", [$this, 'process_prepaid_order'], 10, 1);

        // Handle refunds/cancellations for prepaid orders.
        $withdraw_statuses = $this->settings->get_setting_value_by_id('prepaid_withdraw_order_statuses', ['refunded' => 'on', 'cancelled' => 'on']); // Default
        if (is_array($withdraw_statuses)) {
            foreach ($withdraw_statuses as $status => $enabled) {
                if ('on' === $enabled) {
                    if ('refunded' === $status) {
                        add_action('woocommerce_order_refunded', [$this, 'handle_prepaid_withdrawal_refund'], 10, 2);
                    } else {
                        // Hook into other statuses like cancelled, failed etc.
                        add_action('woocommerce_order_status_' . $status, [$this, 'handle_prepaid_withdrawal'], 10, 1);
                    }
                }
            }
        }
    }

    /**
     * Process a prepaid top-up order.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function process_prepaid_order(int $order_id): void
    {
        $this->logger->info("Starting to process prepaid order: {$order_id}", ['order_id' => $order_id], true);
        $order = $this->wpRepository->get_order($order_id);
        if (! $order) {
            $this->logger->error("Order not found for prepaid processing: {$order_id}");
            return;
        }

        // Check if already processed
        if ($this->wpRepository->were_prepaid_transactions_created($order_id)) {
            $this->logger->info('Prepaid transactions already created for order', ['order_id' => $order_id], true);
            OrderNotes::add_warning($order, 'Attempted to process prepaid top-up again, but it was already processed.');
            return;
        }

        $items = $this->wpRepository->get_order_items($order_id);
        if (empty($items)) {
            $this->logger->error("No items found in prepaid order: {$order_id}");
            return;
        }

        $has_prepaid_items = false;
        $billing_email = $order->get_billing_email();
        if (empty($billing_email)) {
            $this->logger->error("Cannot process prepaid order {$order_id}: Billing email not found.", ['order_id' => $order_id]);
            OrderNotes::add_error($order, __('Could not process prepaid top-up: Billing email is missing from the order.', 'leat-crm'));
            return;
        }

        // Find or create Leat contact using the billing email
        try {
            // Use the injected Connection instance now
            $contact_data = $this->connection->find_or_create_contact($billing_email);
            $buyer_contact_uuid = $contact_data['uuid'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error("Error finding or creating contact for prepaid order {$order_id}: " . $e->getMessage(), ['order_id' => $order_id, 'email' => $billing_email, 'exception' => $e]);
            OrderNotes::add_error($order, sprintf(__('Could not process prepaid top-up: Error communicating with Leat API for email %s.', 'leat-crm'), $billing_email));
            return;
        }

        $shop_uuid = $this->apiService->get_shop_uuid();

        if (! $buyer_contact_uuid) {
            $this->logger->error("Cannot process prepaid order {$order_id}: Buyer Leat contact UUID not found.", ['order_id' => $order_id]);
            OrderNotes::add_error($order, __('Could not process prepaid top-up: Buyer is not linked to a Leat contact.', 'leat-crm'));
            return;
        }

        if (! $shop_uuid) {
            $this->logger->error("Cannot process prepaid order {$order_id}: Shop UUID not configured.", ['order_id' => $order_id]);
            OrderNotes::add_error($order, __('Could not process prepaid top-up: Shop is not configured correctly in Leat settings.', 'leat-crm'));
            return;
        }

        $this->logger->info("Processing prepaid for order {$order_id}, Contact: {$buyer_contact_uuid}, Shop: {$shop_uuid}", ['order_id' => $order_id], true);

        foreach ($items as $item) {
            /** @var WC_Order_Item_Product $item */
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $effective_product_id = $variation_id ?: $product_id;

            if (! $effective_product_id || ! $this->wpRepository->is_prepaid_product($effective_product_id)) {
                continue; // Skip non-prepaid products
            }

            $has_prepaid_items = true;
            $product = $item->get_product(); // Get product associated with the item
            if (! $product) {
                $this->logger->warning("Could not get product for item ID {$item->get_id()} in order {$order_id}", ['order_id' => $order_id]);
                continue;
            }

            $quantity = $item->get_quantity();
            $this->logger->info("Found prepaid item in order {$order_id}: Product ID {$effective_product_id}, Quantity: {$quantity}", ['order_id' => $order_id], true);

            OrderNotes::add(
                $order,
                sprintf(
                    // translators: 1: quantity, 2: product name.
                    __('Starting to process %1$d prepaid top-up(s) for %2$s.', 'leat-crm'),
                    $quantity,
                    $product->get_name()
                )
            );

            for ($i = 1; $i <= $quantity; $i++) {
                $amount_in_cents = $this->calculate_prepaid_amount($product, $item, $i, $order_id);
                if ($amount_in_cents <= 0) {
                    OrderNotes::add_error(
                        $order,
                        sprintf(
                            // translators: 1: product name.
                            __('Invalid amount (0 or negative) for prepaid top-up for %1$s.', 'leat-crm'),
                            $product->get_name()
                        )
                    );
                    continue;
                }

                $transaction = $this->leatRepository->create_transaction($buyer_contact_uuid, $amount_in_cents, $shop_uuid);

                if ($transaction && $transaction->getUuid()) {
                    $tx_uuid = $transaction->getUuid();
                    $this->wpRepository->save_transaction_uuid_for_item($item->get_id(), $tx_uuid, $i);
                    OrderNotes::add_success(
                        $order,
                        sprintf(
                            // translators: 1: amount, 2: Transaction UUID
                            __('Successfully processed prepaid top-up of %1$s. Transaction ID: %2$s', 'leat-crm'),
                            wc_price($amount_in_cents / 100),
                            $tx_uuid
                        )
                    );
                } else {
                    OrderNotes::add_error(
                        $order,
                        sprintf(
                            // translators: 1: product name.
                            __('Failed to create prepaid transaction via API for %1$s.', 'leat-crm'),
                            $product->get_name()
                        )
                    );
                }
            }
        }

        if ($has_prepaid_items) {
            $this->wpRepository->mark_prepaid_transactions_created($order_id);
        }
        $this->logger->info("Finished processing prepaid order: {$order_id}", ['order_id' => $order_id], true);
    }

    /**
     * Calculate the prepaid top-up amount based on product price.
     *
     * @param WC_Product            $product The product object.
     * @param WC_Order_Item_Product $item The order item object.
     * @param int                   $quantity_index The index within the total quantity (for item total calculation).
     * @param int                   $order_id The order ID.
     * @return int The amount in cents.
     */
    private function calculate_prepaid_amount($product, $item, $quantity_index, $order_id): int
    {
        /**
         * WPC Name Your Price for WooCommerce integration.
         * Check if the plugin is active and the product is suitable.
         * @see https://wordpress.org/plugins/wpc-name-your-price/
         */
        if (class_exists('WPCleverWoonp') && $product->is_type('simple')) {
            $item_total = $item->get_total(); // WPC usually stores the named price in the total.
            $item_quantity = $item->get_quantity();

            if ($item_quantity > 0) {
                $amount_per_unit = $item_total / $item_quantity;
                $this->logger->info("Calculated WPC Name Your Price amount per unit: " . ($amount_per_unit / 100), ['order_id' => $order_id, 'item_id' => $item->get_id()]);
                return (int) round($amount_per_unit * 100);
            } else {
                $this->logger->warning("WPC Name Your Price: Item quantity is zero for item ID {$item->get_id()} in order {$order_id}", ['order_id' => $order_id]);
                // Fall through to default logic if quantity is zero
            }
        }

        // Default calculation: Use item subtotal to reflect the price *at the time of purchase* per unit
        $item_subtotal = $item->get_subtotal();
        $item_quantity = $item->get_quantity();

        if ($item_quantity > 0) {
            // Amount per single unit
            $amount_per_unit = $item_subtotal / $item_quantity;
            $this->logger->info("Calculated default amount per unit: " . ($amount_per_unit / 100), ['order_id' => $order_id, 'item_id' => $item->get_id()], true);
            return (int) round($amount_per_unit * 100);
        } else {
            $this->logger->warning("Item quantity is zero or invalid for item ID {$item->get_id()} in order {$order_id}", ['order_id' => $order_id]);
            // Fallback to product price if quantity is weird, though this shouldn't happen
            $price = $product->get_price();
            $this->logger->info("Falling back to product price: " . ($price), ['order_id' => $order_id, 'item_id' => $item->get_id()]);
            return (int) round($price * 100);
        }
    }

    /**
     * Handle prepaid transaction reversal for refunds.
     *
     * @param int $order_id The order ID.
     * @param int $refund_id The refund ID.
     * @return void
     */
    public function handle_prepaid_withdrawal_refund(int $order_id, int $refund_id): void
    {
        $this->logger->info("Handling prepaid withdrawal for refund. Order: {$order_id}, Refund: {$refund_id}", ['order_id' => $order_id, 'refund_id' => $refund_id], true);
        $order = $this->wpRepository->get_order($order_id);
        $refund = $this->wpRepository->get_order($refund_id); // Refunds are orders

        if (! $order || ! $refund) {
            $this->logger->error("Could not find order or refund object for prepaid withdrawal.", ['order_id' => $order_id, 'refund_id' => $refund_id]);
            return;
        }

        $refund_items = $refund->get_items();
        if (empty($refund_items)) {
            $this->logger->info("No items in refund {$refund_id}, skipping prepaid withdrawal.", ['refund_id' => $refund_id]);
            return;
        }

        foreach ($refund_items as $refund_item) {
            /** @var WC_Order_Item_Product $refund_item */
            $original_item_id = wc_get_order_item_meta($refund_item->get_id(), '_refunded_item_id', true);
            if (! $original_item_id) {
                continue;
            }

            $original_item = $order->get_item($original_item_id);
            if (! $original_item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product_id = $original_item->get_product_id();
            $variation_id = $original_item->get_variation_id();
            $effective_product_id = $variation_id ?: $product_id;

            if (! $effective_product_id || ! $this->wpRepository->is_prepaid_product($effective_product_id)) {
                continue; // Not a prepaid product
            }

            $refunded_qty = abs($refund_item->get_quantity()); // Qty is negative in refund item
            $original_qty = $original_item->get_quantity();

            // Identify which specific transaction UUIDs correspond to the refunded quantity.
            // Assume transactions are linked 1-to-1 with quantity index (e.g., _leat_prepaid_transaction_uuid_1, _2, etc.)
            // We need to reverse the *last* N transactions, where N is the refunded quantity.
            for ($i = $original_qty; $i > ($original_qty - $refunded_qty); $i--) {
                $transaction_uuid = $this->wpRepository->get_transaction_uuid_for_item($original_item->get_id(), $i);
                if (! $transaction_uuid) {
                    $this->logger->warning("Could not find prepaid transaction UUID for item #{$original_item->get_id()}, index {$i} during refund.", ['order_id' => $order_id, 'refund_id' => $refund_id]);
                    continue;
                }

                $this->reverse_prepaid_transaction($transaction_uuid, $order);
            }
        }
        $this->logger->info("Finished prepaid withdrawal for refund. Order: {$order_id}, Refund: {$refund_id}", ['order_id' => $order_id, 'refund_id' => $refund_id], true);
    }

    /**
     * Handle prepaid transaction reversal for non-refund status changes (e.g., cancelled).
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function handle_prepaid_withdrawal(int $order_id): void
    {
        $this->logger->info("Handling prepaid withdrawal for status change. Order: {$order_id}", ['order_id' => $order_id], true);
        $order = $this->wpRepository->get_order($order_id);
        if (! $order) {
            $this->logger->error("Order not found for prepaid withdrawal: {$order_id}");
            return;
        }

        // Only proceed if transactions were created in the first place
        if (! $this->wpRepository->were_prepaid_transactions_created($order_id)) {
            $this->logger->info("Skipping prepaid withdrawal for order {$order_id}: Transactions were never created.", ['order_id' => $order_id]);
            return;
        }

        $items = $this->wpRepository->get_order_items($order_id);
        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            /** @var WC_Order_Item_Product $item */
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $effective_product_id = $variation_id ?: $product_id;

            if (! $effective_product_id || ! $this->wpRepository->is_prepaid_product($effective_product_id)) {
                continue;
            }

            $quantity = $item->get_quantity();
            for ($i = 1; $i <= $quantity; $i++) {
                $transaction_uuid = $this->wpRepository->get_transaction_uuid_for_item($item->get_id(), $i);
                if (! $transaction_uuid) {
                    $this->logger->warning("Could not find prepaid transaction UUID for item #{$item->get_id()}, index {$i} during status change withdrawal.", ['order_id' => $order_id]);
                    continue;
                }
                $this->reverse_prepaid_transaction($transaction_uuid, $order);
            }
        }
        $this->logger->info("Finished prepaid withdrawal for status change. Order: {$order_id}", ['order_id' => $order_id], true);
    }

    /**
     * Helper function to reverse a single prepaid transaction and add order notes.
     *
     * @param string $transaction_uuid The transaction UUID to reverse.
     * @param WC_Order $order The order object for adding notes.
     * @return void
     */
    private function reverse_prepaid_transaction(string $transaction_uuid, WC_Order $order): void
    {
        try {
            $reversal_transaction = $this->leatRepository->reverse_transaction($transaction_uuid);
            if ($reversal_transaction && $reversal_transaction->getUuid()) {
                OrderNotes::add_success(
                    $order,
                    sprintf(
                        /* translators: 1: Original Transaction UUID, 2: Reversal Transaction UUID */
                        __('Prepaid transaction %1$s reversed due to order status change/refund. Reversal Transaction ID: %2$s', 'leat-crm'),
                        $transaction_uuid,
                        $reversal_transaction->getUuid()
                    )
                );
            } else {
                OrderNotes::add_error(
                    $order,
                    sprintf(
                        /* translators: %s: Original Transaction UUID */
                        __('Failed to reverse prepaid transaction %s via API.', 'leat-crm'),
                        $transaction_uuid
                    )
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception reversing prepaid transaction {$transaction_uuid}: " . $e->getMessage(), ['order_id' => $order->get_id(), 'exception' => $e]);
            OrderNotes::add_error(
                $order,
                sprintf(
                    /* translators: 1: Original Transaction UUID, 2: Error message */
                    __('Error reversing prepaid transaction %1$s: %2$s', 'leat-crm'),
                    $transaction_uuid,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Add prepaid product tab to WooCommerce product data tabs.
     *
     * @param array $tabs The existing tabs.
     * @return array The modified tabs.
     */
    public function add_prepaid_product_tab(array $tabs): array
    {
        $tabs['leat_prepaid'] = [
            'label'  => __('Leat: Prepaid', 'leat-crm'),
            'target' => 'leat_prepaid_product_data',
            'class'  => ['show_if_simple', 'show_if_variable'], // Show for simple and variable products
        ];
        return $tabs;
    }

    /**
     * Add prepaid product settings to WooCommerce product data panels.
     *
     * @return void
     */
    public function add_prepaid_product_settings(): void
    {
        global $post;
        $product_id = $post->ID;

        // Verify user permissions.
        if (! current_user_can('edit_product', $product_id)) {
            return;
        }

        echo '<div id="leat_prepaid_product_data" class="panel woocommerce_options_panel">';

        woocommerce_wp_checkbox(
            [
                'id'          => WCOrders::IS_PREPAID_PRODUCT,
                'label'       => __('Is Prepaid Top-up Product?', 'leat-crm'),
                'description' => __('Enable this to make purchasing this product add value to the customer\'s Leat prepaid balance.', 'leat-crm'),
                'desc_tip'    => true,
                'value'       => $this->wpRepository->is_prepaid_product($product_id) ? 'yes' : 'no',
            ]
        );

        echo '</div>';
    }

    /**
     * Save prepaid product settings from WooCommerce product meta.
     *
     * @param int $product_id The product ID.
     * @return void
     */
    public function save_prepaid_product_settings(int $product_id): void
    {
        // No nonce needed here as WC handles it via woocommerce_process_product_meta

        if (! current_user_can('edit_product', $product_id)) {
            return;
        }

        $is_prepaid = isset($_POST[WCOrders::IS_PREPAID_PRODUCT]);
        $this->wpRepository->save_is_prepaid_product($product_id, $is_prepaid);
    }
}
