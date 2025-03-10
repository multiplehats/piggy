<?php

namespace Leat\Domain\Interfaces;


/**
 * Interface WPGiftcardCouponRepositoryInterface
 *
 * Interface for WordPress gift card coupon repository implementations.
 *
 * @package Leat\Domain\Interfaces
 */
interface WPGiftcardCouponRepositoryInterface
{
    /**
     * Find a gift card coupon by its hash.
     *
     * @param string $hash The gift card hash.
     * @return object|null The coupon object or null if not found.
     */
    public function find_by_hash(string $hash): ?object;

    /**
     * Find a gift card coupon by its UUID.
     *
     * @param string $uuid The gift card UUID.
     * @return object|null The coupon object or null if not found.
     */
    public function find_by_uuid(string $uuid): ?object;

    /**
     * Create a new gift card coupon.
     *
     * @param array $data The gift card data.
     * @return object The created coupon object.
     */
    public function create(array $data): object;

    /**
     * Update a gift card coupon.
     *
     * @param \WC_Coupon $coupon The coupon object.
     * @param array $data The gift card data to update.
     * @return \WC_Coupon The updated coupon object.
     */
    public function update(\WC_Coupon $coupon, array $data): \WC_Coupon;

    /**
     * Get the current balance of a gift card.
     *
     * @param string $uuid The gift card UUID.
     * @return int|null The current balance in cents or null if not found.
     */
    public function get_balance(string $uuid): ?int;

    /**
     * Update the balance of a gift card coupon.
     *
     * @param object $coupon The coupon object.
     * @param int $balance The new balance in cents.
     * @return object The updated coupon object.
     */
    public function update_balance(\WC_Coupon $coupon, int $balance): \WC_Coupon;

    /**
     * Check if a coupon is a gift card.
     *
     * @param object $coupon The coupon object.
     * @return bool Whether the coupon is a gift card.
     */
    public function is_giftcard(\WC_Coupon $coupon): bool;
}
