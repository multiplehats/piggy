<?php

namespace Leat\Infrastructure\Repositories;

use Leat\Domain\Interfaces\WPGiftcardCouponRepositoryInterface;
use Leat\Infrastructure\Constants\WCCoupons;
use Leat\Utils\Logger;

/**
 * Class WPGiftcardCouponRepository
 *
 * WordPress-specific implementation for gift card coupon data access.
 *
 * @package Leat\Infrastructure\Repositories
 */
class WPGiftcardCouponRepository implements WPGiftcardCouponRepositoryInterface
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logger = new Logger('giftcard-coupon-repository');
    }

    /**
     * Find a gift card coupon by its hash.
     *
     * @param string $hash The gift card hash.
     * @return object|null The coupon object or null if not found.
     */
    public function find_by_hash(string $hash): ?object
    {
        global $wpdb;

        $coupon_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = %s AND meta_value = %s
                LIMIT 1",
                WCCoupons::GIFTCARD_HASH,
                $hash
            )
        );

        if (!$coupon_id) {
            return null;
        }

        try {
            return new \WC_Coupon($coupon_id);
        } catch (\Exception $e) {
            $this->logger->error('Error finding gift card coupon by hash', [
                'hash' => $hash,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find a gift card coupon by its UUID.
     *
     * @param string $uuid The gift card UUID.
     * @return object|null The coupon object or null if not found.
     */
    public function find_by_uuid(string $uuid): ?object
    {
        global $wpdb;

        $coupon_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = %s AND meta_value = %s
                LIMIT 1",
                WCCoupons::GIFTCARD_UUID,
                $uuid
            )
        );

        if (!$coupon_id) {
            return null;
        }

        try {
            return new \WC_Coupon($coupon_id);
        } catch (\Exception $e) {
            $this->logger->error('Error finding gift card coupon by UUID', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a new gift card coupon.
     *
     * @param array $data The gift card data.
     * @return object The created coupon object.
     */
    public function create(array $data): object
    {
        try {
            $coupon = new \WC_Coupon();

            // Set basic coupon properties
            $coupon->set_code($data['hash']);
            $coupon->set_discount_type('fixed_cart');

            $coupon->set_amount($data['balance_in_cents'] / 100);
            $coupon->set_individual_use(true);
            $coupon->set_usage_limit(0); // Unlimited usage
            $coupon->set_usage_limit_per_user(0);

            // Set description
            $coupon->set_description(sprintf(
                __('Gift Card %s - Initial Balance: %s', 'leat-crm'),
                $data['hash'],
                html_entity_decode(strip_tags(wc_price($data['balance_in_cents'] / 100)))
            ));

            error_log(print_r($data, true));

            // Set gift card specific meta data
            $coupon->update_meta_data(WCCoupons::GIFTCARD_UUID, $data['uuid']);
            $coupon->update_meta_data(WCCoupons::GIFTCARD_HASH, $data['hash']);
            $coupon->update_meta_data(WCCoupons::GIFTCARD_PROGRAM_UUID, $data['program_uuid']);
            $coupon->update_meta_data(WCCoupons::GIFTCARD_INITIAL_BALANCE, $data['balance_in_cents']);
            $coupon->update_meta_data(WCCoupons::GIFTCARD_CURRENT_BALANCE, $data['balance_in_cents']);
            $coupon->update_meta_data(WCCoupons::GIFTCARD_LAST_CHECKED, time());
            $coupon->update_meta_data(WCCoupons::GIFTCARD_TYPE, 'giftcard');

            $coupon->save();

            return $coupon;
        } catch (\Exception $e) {
            $this->logger->error('Error creating gift card coupon', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update a gift card coupon.
     *
     * @param \WC_Coupon $coupon The coupon object.
     * @param array $data The gift card data to update.
     * @return \WC_Coupon The updated coupon object.
     */
    public function update(\WC_Coupon $coupon, array $data): \WC_Coupon
    {
        try {
            if (isset($data['balance'])) {
                $coupon->set_amount($data['balance'] / 100); // Convert cents to currency units
                $coupon->update_meta_data(WCCoupons::GIFTCARD_CURRENT_BALANCE, $data['balance']);
            }

            if (isset($data['program_uuid'])) {
                $coupon->update_meta_data(WCCoupons::GIFTCARD_PROGRAM_UUID, $data['program_uuid']);
            }

            $coupon->update_meta_data(WCCoupons::GIFTCARD_LAST_CHECKED, time());

            $coupon->save();

            return $coupon;
        } catch (\Exception $e) {
            $this->logger->error('Error updating gift card coupon', [
                'coupon_id' => $coupon->get_id(),
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the current balance of a gift card.
     *
     * @param string $uuid The gift card UUID.
     * @return int|null The current balance in cents or null if not found.
     */
    public function get_balance(string $uuid): ?int
    {
        $coupon = $this->find_by_uuid($uuid);

        if (!$coupon) {
            return null;
        }

        return (int) $coupon->get_meta(WCCoupons::GIFTCARD_CURRENT_BALANCE);
    }

    /**
     * Update the balance of a gift card coupon.
     *
     * @param \WC_Coupon $coupon The coupon object.
     * @param int $balance The new balance in cents.
     * @return \WC_Coupon The updated coupon object.
     */
    public function update_balance(\WC_Coupon $coupon, int $balance_in_cents): \WC_Coupon
    {
        try {
            $coupon->set_amount($balance_in_cents / 100);
            $coupon->update_meta_data(WCCoupons::GIFTCARD_CURRENT_BALANCE, $balance_in_cents);
            $coupon->update_meta_data(WCCoupons::GIFTCARD_LAST_CHECKED, time());

            if ($balance_in_cents === 0) {
                /**
                 * Have to use wp_update_post, because $coupon->set_status() doesn't work ¯\_(ツ)_/¯
                 */
                wp_update_post([
                    'ID' => $coupon->get_id(),
                    'post_status' => 'draft'
                ]);
            } else {
                wp_update_post([
                    'ID' => $coupon->get_id(),
                    'post_status' => 'publish'
                ]);
            }

            $coupon->save();

            return $coupon;
        } catch (\Exception $e) {
            $this->logger->error('Error updating gift card balance', [
                'coupon_id' => $coupon->get_id(),
                'balance' => $balance_in_cents,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if a coupon is a gift card.
     *
     * @param \WC_Coupon $coupon The coupon object.
     * @return bool Whether the coupon is a gift card.
     */
    public function is_giftcard(\WC_Coupon $coupon): bool
    {
        if (!$coupon || !$coupon->get_id()) {
            $this->logger->error('Invalid coupon object passed to is_giftcard');
            return false;
        }

        return $coupon->get_meta(WCCoupons::GIFTCARD_TYPE) === 'giftcard';
    }
}
