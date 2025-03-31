<?php

namespace Leat\Domain\Interfaces;

/**
 * Interface GiftcardProductServiceInterface
 *
 * Defines the contract for gift card product business logic operations.
 *
 * @package Leat\Domain\Interfaces
 */
interface GiftcardProductServiceInterface
{
    /**
     * Initialize the service and register hooks.
     *
     * @return void
     */
    public function init(): void;

    /**
     * Process a gift card order.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function process_giftcard_order($order_id): void;

    /**
     * Handle gift card withdrawal for refunds.
     *
     * @param int $order_id The order ID.
     * @param int $refund_id The refund ID.
     * @return void
     */
    public function handle_giftcard_withdrawal_refund($order_id, $refund_id): void;

    /**
     * Handle gift card withdrawal for order status changes.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function handle_giftcard_withdrawal($order_id): void;

    /**
     * Validate gift card recipient email.
     *
     * @return void
     */
    public function validate_giftcard_recipient_email(): void;

    /**
     * Add gift card product tab to product tabs.
     *
     * @param array $tabs The product tabs.
     * @return array Modified tabs array.
     */
    public function add_giftcard_product_tab($tabs): array;

    /**
     * Add gift card program settings to the product.
     *
     * @return void
     */
    public function add_giftcard_program_settings(): void;

    /**
     * Save gift card program settings.
     *
     * @param int $post_id The post ID.
     * @return void
     */
    public function save_giftcard_program_settings($post_id): void;

    /**
     * Add gift card recipient field to checkout.
     *
     * @param object $checkout The checkout object.
     * @return void
     */
    public function add_giftcard_recipient_field($checkout): void;

    /**
     * Save gift card recipient email.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function save_giftcard_recipient_email($order_id): void;

    /**
     * Format gift card meta display.
     *
     * @param string $display_value The display value.
     * @param object $meta The meta object.
     * @param object $item The item object.
     * @return string The formatted display value.
     */
    public function format_giftcard_meta_display($display_value, $meta, $item): string;

    /**
     * Add refund field script.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function add_refund_field_script($order_id): void;
}
