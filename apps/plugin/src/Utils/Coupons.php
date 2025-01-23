<?php

namespace Leat\Utils;

/**
 * Common Helper class.
 */
class Coupons
{
    /**
     * Find or create a coupon.
     *
     * @param string $voucher_code
     * @return \WC_Coupon
     */
    public static function find_or_create_coupon_by_code($voucher_code)
    {
        try {
            // Try to load existing coupon.
            $coupon = new \WC_Coupon($voucher_code);

            // If it exists, we don't need to do anything.
            if ($coupon) {
                return $coupon;
            }
        } catch (\Exception $e) {
            // Coupon doesn't exist, create new one.
            $coupon = new \WC_Coupon();

            $coupon->set_code(strtoupper($voucher_code));
        }

        return $coupon;
    }

    /**
     * Find a coupon by its code.
     *
     * @param string $voucher_code
     * @return \WC_Coupon
     */
    public static function find_coupon_by_code($voucher_code)
    {
        return new \WC_Coupon($voucher_code);
    }

    /**
     * Find coupons by email. Unfortunately, WooCommerce doesn't store the email in the coupon meta table, so we have to use this workaround.
     *
     * @param string $email
     * @return array<\WC_Coupon> The list of coupons found.
     */
    public static function find_coupons_by_email($email)
    {
        global $wpdb;

        $coupon_codes = [];

        $coupon_codes = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT p.post_name
                FROM {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta pm2
                    ON p.ID = pm2.post_id
                WHERE p.post_type = 'shop_coupon'
                    AND p.post_status = 'publish'
                    AND pm2.meta_key = 'customer_email'
                    AND pm2.meta_value LIKE %s
                ORDER BY p.post_name DESC",
                '%' . $wpdb->esc_like($email) . '%'
            )
        );

        $coupons = array_filter(array_map(function ($code) {
            try {
                return new \WC_Coupon($code);
            } catch (\Exception $e) {
                return null;
            }
        }, $coupon_codes));

        return array_filter($coupons, function ($coupon) {
            return $coupon !== null;
        });
    }
}
