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

    /**
     * Get all valid coupons for a specific user.
     *
     * @param \WP_User $user WordPress user object
     * @return array Array of valid coupon data
     */
    public static function get_coupons_for_user(\WP_User $user): array
    {
        $coupons = [];
        $email_coupons = self::find_coupons_by_email($user->user_email);

        foreach ($email_coupons as $coupon) {
            if (self::is_coupon_valid($coupon, $user)) {
                $coupons[] = [
                    'id' => $coupon->get_id(),
                    'code' => $coupon->get_code(),
                    'type' => $coupon->get_discount_type(),
                    'amount' => $coupon->get_amount(),
                    'expiry_date' => $coupon->get_date_expires() ?
                        $coupon->get_date_expires()->format('Y-m-d') : null,
                    'usage_count' => $coupon->get_usage_count(),
                    'usage_limit' => $coupon->get_usage_limit(),
                    'description' => $coupon->get_description()
                ];
            }
        }

        return $coupons;
    }

    /**
     * Check if a coupon is still valid.
     *
     * @param \WC_Coupon $coupon WooCommerce coupon object
     * @return bool Whether the coupon is valid
     */
    private static function is_coupon_valid(\WC_Coupon $coupon, \WP_User $user): bool
    {
        // Check if coupon is expired
        $expiry_date = $coupon->get_date_expires();
        if ($expiry_date && current_time('timestamp') > $expiry_date->getTimestamp()) {
            return false;
        }

        // Check usage limits
        $usage_limit = $coupon->get_usage_limit();
        if ($usage_limit && $coupon->get_usage_count() >= $usage_limit) {
            return false;
        }

        // Skip if per user usage limit is reached
        if ($coupon->get_usage_limit_per_user() > 0) {
            $used_by = $coupon->get_used_by();
            $user_usage_count = count(array_filter($used_by, function ($user_data) use ($user) {
                return $user_data == $user->ID || $user_data == $user->user_email;
            }));
            if ($user_usage_count >= $coupon->get_usage_limit_per_user()) {
                return false;
            }
        }

        return true;
    }
}
