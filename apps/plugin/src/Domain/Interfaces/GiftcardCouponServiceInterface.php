<?php

namespace Leat\Domain\Interfaces;

use Piggy\Api\Models\Giftcards\Giftcard;

/**
 * Interface GiftcardCouponServiceInterface
 *
 * Interface for gift card coupon service implementations.
 *
 * @package Leat\Domain\Interfaces
 */
interface GiftcardCouponServiceInterface
{
    /**
     * Initialize the service and register hooks.
     *
     * @return void
     */
    public function init(): void;

    /**
     * Create a gift card coupon from a Leat gift card.
     *
     * @param Giftcard $giftcard The gift card data.
     * @return \WC_Coupon|null The created coupon object or null on failure.
     */
    public function create_giftcard_coupon(Giftcard $giftcard): ?\WC_Coupon;

    /**
     * Validate a gift card coupon before it's applied.
     *
     * @param bool $valid Whether the coupon is valid.
     * @param \WC_Coupon $coupon The coupon object.
     * @param \WC_Discounts $discounts The discounts object.
     * @return bool Whether the coupon is valid.
     */
    public function validate_giftcard_coupon(bool $valid, \WC_Coupon $coupon, \WC_Discounts $discounts): bool;

    /**
     * Check the balance of a gift card in Leat.
     *
     * @param Giftcard $giftcard The gift card object.
     * @return int|null The current balance in cents or null if not found.
     */
    public function check_giftcard_balance(Giftcard $giftcard): ?int;

    /**
     * Update the balance of a gift card coupon after an order is processed.
     *
     * @param int $order_id The order ID.
     * @return void
     */
    public function update_giftcard_balance_after_order(int $order_id): void;

    /**
     * Handle gift card coupon refunds.
     *
     * @param int $order_id The order ID.
     * @param int $refund_id The refund ID.
     * @return void
     */
    public function handle_giftcard_coupon_refund(int $order_id, int $refund_id): void;

    /**
     * Register gift card coupon meta box.
     *
     * @return void
     */
    public function register_giftcard_coupon_meta_box(): void;

    /**
     * Save gift card coupon meta box data.
     *
     * @param int $post_id The post ID.
     * @return void
     */
    public function save_giftcard_coupon_meta_box(int $post_id): void;
}
