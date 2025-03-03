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
}
