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
}
