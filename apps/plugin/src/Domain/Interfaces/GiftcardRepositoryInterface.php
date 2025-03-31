<?php

namespace Leat\Domain\Interfaces;

/**
 * Interface GiftcardRepositoryInterface
 *
 * Defines the contract for gift card data access operations.
 *
 * @package Leat\Domain\Interfaces
 */
interface GiftcardRepositoryInterface
{
    /**
     * Get gift card program UUID for a product.
     *
     * @param int $product_id The product ID.
     * @return string|null The program UUID or null if not found.
     */
    public function get_program_uuid_for_product($product_id): ?string;

    /**
     * Get gift card recipient email for an order.
     *
     * @param int $order_id The order ID.
     * @return string|null The recipient email or null if not found.
     */
    public function get_recipient_email_for_order($order_id): ?string;

    /**
     * Save gift card recipient email for an order.
     *
     * @param int $order_id The order ID.
     * @param string $email The recipient email.
     * @return void
     */
    public function save_recipient_email_for_order($order_id, $email): void;

    /**
     * Save gift card program UUID for a product.
     *
     * @param int $product_id The product ID.
     * @param string $program_uuid The program UUID.
     * @return void
     */
    public function save_program_uuid_for_product($product_id, $program_uuid): void;

    /**
     * Get order items for an order.
     *
     * @param int $order_id The order ID.
     * @return array The order items.
     */
    public function get_order_items($order_id): array;

    /**
     * Get order by ID.
     *
     * @param int $order_id The order ID.
     * @return object|null The order object or null if not found.
     */
    public function get_order($order_id): ?object;

    /**
     * Add order note.
     *
     * @param int $order_id The order ID.
     * @param string $note The note content.
     * @param bool $is_customer_note Whether this is a customer note.
     * @return void
     */
    public function add_order_note($order_id, $note, $is_customer_note = false): void;
}
