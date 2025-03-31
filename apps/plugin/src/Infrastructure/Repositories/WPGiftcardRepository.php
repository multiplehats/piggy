<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Infrastructure\Constants\WCOrders;

/**
 * Class WPGiftcardRepository
 *
 * WordPress-specific implementation for gift card data access.
 *
 * @package Leat\Infrastructure\Repositories
 */
class WPGiftcardRepository
{
    /**
     * Get gift card program UUID for a product.
     *
     * @param int $product_id The product ID.
     * @return string|null The program UUID or null if not found.
     */
    public function get_program_uuid_for_product($product_id): ?string
    {
        $program_uuid = get_post_meta($product_id, '_leat_giftcard_program_uuid', true);
        return !empty($program_uuid) ? $program_uuid : null;
    }

    /**
     * Get gift card recipient email for an order.
     *
     * @param int $order_id The order ID.
     * @return string|null The recipient email or null if not found.
     */
    public function get_recipient_email_for_order($order_id): ?string
    {
        $email = get_post_meta($order_id, WCOrders::GIFT_CARD_RECIPIENT_EMAIL, true);
        return !empty($email) ? $email : null;
    }

    /**
     * Save gift card recipient email for an order.
     *
     * @param int $order_id The order ID.
     * @param string $email The recipient email.
     * @return void
     */
    public function save_recipient_email_for_order($order_id, $email): void
    {
        update_post_meta($order_id, WCOrders::GIFT_CARD_RECIPIENT_EMAIL, sanitize_email(wp_unslash($email)));
    }

    /**
     * Save gift card program UUID for a product.
     *
     * @param int $product_id The product ID.
     * @param string $program_uuid The program UUID.
     * @return void
     */
    public function save_program_uuid_for_product($product_id, $program_uuid): void
    {
        update_post_meta($product_id, '_leat_giftcard_program_uuid', sanitize_text_field($program_uuid));
    }

    /**
     * Get order items for an order.
     *
     * @param int $order_id The order ID.
     * @return array The order items.
     */
    public function get_order_items($order_id): array
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return [];
        }

        return $order->get_items();
    }

    /**
     * Get order by ID.
     *
     * @param int $order_id The order ID.
     * @return object|null The order object or null if not found.
     */
    public function get_order($order_id): ?object
    {
        $order = wc_get_order($order_id);

        return $order ? $order : null;
    }

    /**
     * Add order note.
     *
     * @param int $order_id The order ID.
     * @param string $note The note content.
     * @param bool $is_customer_note Whether this is a customer note.
     * @return void
     */
    public function add_order_note($order_id, $note, $is_customer_note = false): void
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->add_order_note($note, $is_customer_note);
        }
    }
}
