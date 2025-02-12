<?php

namespace Leat\Domain\Services;

use Leat\Utils\Coupons;
use Leat\Utils\Logger;

class CouponManager
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * Updates coupons for a promotion rule
     *
     * @param array $promotion_rule The promotion rule data
     * @return void
     */
    public function update_coupons_for_promotion_rule($promotion_rule)
    {
        $args = array(
            'post_type' => 'shop_coupon',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_leat_promotion_rule_id',
                    'value' => $promotion_rule['id'],
                    'compare' => '=',
                ),
            ),
        );

        $coupons = get_posts($args);

        if ($coupons) {
            foreach ($coupons as $coupon_post) {
                $this->update_coupon($coupon_post, $promotion_rule);
            }
        }
    }

    /**
     * Updates a single coupon
     *
     * @param \WP_Post $coupon_post The coupon post
     * @param array $promotion_rule The promotion rule data
     * @return void
     */
    private function update_coupon($coupon_post, $promotion_rule)
    {
        try {
            $coupon = new \WC_Coupon($coupon_post->ID);

            // Update basic coupon settings
            if ($promotion_rule['individualUse']['value'] === 'on') {
                $coupon->set_individual_use(true);
            } else {
                $coupon->set_individual_use(false);
            }

            // Set discount type and amount
            $discount_type = $this->get_discount_type($promotion_rule['discountType']['value']);
            if ($discount_type) {
                $coupon->set_discount_type($discount_type);
            }

            $discount_value = $promotion_rule['discountValue']['value'];
            if ($discount_value) {
                $coupon->set_amount($discount_value);
            }

            // Set minimum purchase amount
            if (
                isset($promotion_rule['minimumPurchaseAmount']) &&
                is_numeric($promotion_rule['minimumPurchaseAmount']['value'])
            ) {
                $min_amount = floatval($promotion_rule['minimumPurchaseAmount']['value']);
                if ($min_amount > 0) {
                    $coupon->set_minimum_amount($min_amount);
                }
            }

            // Update product restrictions
            if (isset($promotion_rule['selectedProducts']['value'])) {
                $coupon->set_product_ids($promotion_rule['selectedProducts']['value']);
            }

            $coupon->save();
        } catch (\Throwable $th) {
            $this->logger->error('Failed to update coupon: ' . $th->getMessage(), [
                'coupon_id' => $coupon_post->ID,
                'promotion_rule_id' => $promotion_rule['id']
            ]);
        }
    }

    /**
     * Converts internal discount type to WooCommerce discount type
     *
     * @param string $value The internal discount type ('percentage' or 'fixed')
     * @return string|null The WooCommerce discount type or null if invalid
     */
    private function get_discount_type($value)
    {
        if ('percentage' === $value) {
            return 'percent';
        } elseif ('fixed' === $value) {
            return 'fixed_product';
        }
        return null;
    }
}
