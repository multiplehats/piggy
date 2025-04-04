<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Infrastructure\Constants\WCOrders;
use WC_Order;
use WC_Product;
use WC_Order_Item;
use WC_Order_Item_Product;

/**
 * Class WPPrepaidRepository
 *
 * Handles interactions with WordPress/WooCommerce data for prepaid products.
 *
 * @package Leat\Infrastructure\Repositories
 */
class WPPrepaidRepository
{
    /**
     * Check if a product is marked as a prepaid product.
     *
     * @param int $product_id The product ID.
     * @return bool True if it is a prepaid product, false otherwise.
     */
    public function is_prepaid_product(int $product_id): bool
    {
        return get_post_meta($product_id, WCOrders::IS_PREPAID_PRODUCT, true) === 'yes';
    }

    /**
     * Save the prepaid product flag for a product.
     *
     * @param int $product_id The product ID.
     * @param bool $is_prepaid Whether the product is prepaid.
     * @return void
     */
    public function save_is_prepaid_product(int $product_id, bool $is_prepaid): void
    {
        update_post_meta($product_id, WCOrders::IS_PREPAID_PRODUCT, $is_prepaid ? 'yes' : 'no');
    }

    /**
     * Get a WooCommerce order by ID.
     *
     * @param int $order_id The order ID.
     * @return WC_Order|false The order object or false if not found.
     */
    public function get_order(int $order_id)
    {
        return wc_get_order($order_id);
    }

    /**
     * Get the items for a specific order.
     *
     * @param int $order_id The order ID.
     * @return array<WC_Order_Item> An array of order item objects.
     */
    public function get_order_items(int $order_id): array
    {
        $order = $this->get_order($order_id);
        if (! $order) {
            return [];
        }
        return $order->get_items();
    }

    /**
     * Save the Leat prepaid transaction UUID as order item meta.
     *
     * @param int $item_id The order item ID.
     * @param string $transaction_uuid The Leat prepaid transaction UUID.
     * @param int $index The index for items with quantity > 1.
     * @return void
     */
    public function save_transaction_uuid_for_item(int $item_id, string $transaction_uuid, int $index = 1): void
    {
        wc_add_order_item_meta($item_id, WCOrders::PREPAID_TRANSACTION_UUID . '_' . $index, $transaction_uuid);
        // Also save the last one without index for potential simpler lookups if only one exists
        if ($index === 1) {
            wc_add_order_item_meta($item_id, WCOrders::PREPAID_TRANSACTION_UUID, $transaction_uuid);
        } else {
            wc_update_order_item_meta($item_id, WCOrders::PREPAID_TRANSACTION_UUID, $transaction_uuid);
        }
    }

    /**
     * Get a Leat prepaid transaction UUID from order item meta.
     *
     * @param int $item_id The order item ID.
     * @param int $index The index for items with quantity > 1.
     * @return string|null The UUID or null if not found.
     */
    public function get_transaction_uuid_for_item(int $item_id, int $index = 1): ?string
    {
        $meta_key = WCOrders::PREPAID_TRANSACTION_UUID . ($index > 1 ? '_' . $index : '');
        $uuid = wc_get_order_item_meta($item_id, $meta_key, true);
        return $uuid ?: null;
    }

    /**
     * Check if prepaid transactions have already been created for an order.
     *
     * @param int $order_id The order ID.
     * @return bool True if created, false otherwise.
     */
    public function were_prepaid_transactions_created(int $order_id): bool
    {
        return get_post_meta($order_id, WCOrders::PREPAID_TRANSACTIONS_CREATED, true) === 'yes';
    }

    /**
     * Mark an order as having its prepaid transactions created.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function mark_prepaid_transactions_created(int $order_id): void
    {
        update_post_meta($order_id, WCOrders::PREPAID_TRANSACTIONS_CREATED, 'yes');
    }

    /**
     * Add a note to a WooCommerce order.
     *
     * @param int $order_id The order ID.
     * @param string $note The note text.
     * @param bool $is_customer_note Whether the note is for the customer.
     * @param bool $added_by_user Whether the note was added by a user.
     * @return int|false The comment ID or false on failure.
     */
    public function add_order_note(int $order_id, string $note, bool $is_customer_note = false, bool $added_by_user = false)
    {
        $order = $this->get_order($order_id);
        if (! $order) {
            return false;
        }
        return $order->add_order_note($note, $is_customer_note, $added_by_user);
    }

    /**
     * Get a product object.
     *
     * @param int $product_id
     * @return WC_Product|false
     */
    public function get_product(int $product_id)
    {
        return wc_get_product($product_id);
    }

    /**
     * Get the product ID from an order item.
     *
     * @param WC_Order_Item $item The order item.
     * @return int|null The product ID or null.
     */
    public function get_product_id_from_item(WC_Order_Item $item): ?int
    {
        if ($item instanceof WC_Order_Item_Product) {
            return $item->get_product_id();
        }
        return null;
    }

    /**
     * Get the buyer's contact UUID from the order.
     *
     * This attempts to get the Leat contact UUID stored on the customer's WordPress user account.
     * It first checks order meta, then falls back to user meta.
     *
     * @param int $order_id The order ID.
     * @return string|null The contact UUID or null if not found or order has no associated user.
     */
    public function get_buyer_contact_uuid(int $order_id): ?string
    {
        $order = $this->get_order($order_id);
        if (!$order) {
            return null; // Order not found
        }

        // 1. Try getting UUID directly from order meta (preferred)
        //    Adjust the meta key if it's different (e.g., '_leat_customer_uuid').
        $contact_uuid = $order->get_meta('_leat_contact_uuid', true);
        if ($contact_uuid) {
            return $contact_uuid;
        }

        // 2. Fallback: Try getting UUID from the associated WP User meta
        $user_id = $order->get_user_id();
        if (!$user_id) {
            return null; // Guest order or user ID not found
        }
        // Adjust the meta key if it's different.
        $contact_uuid = get_user_meta($user_id, 'leat_contact_uuid', true);
        return $contact_uuid ?: null;
    }
}
