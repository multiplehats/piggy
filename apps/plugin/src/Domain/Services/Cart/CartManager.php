<?php

namespace Leat\Domain\Services\Cart;

use Leat\Domain\Services\SpendRules;
use Leat\Utils\Logger;

/**
 * Class CartManager
 */
class CartManager
{
    /**
     * Spend Rules instance.
     *
     * @var SpendRules
     */
    private $spend_rules;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    public function __construct(SpendRules $spend_rules, Logger $logger)
    {
        $this->spend_rules = $spend_rules;
        $this->logger = $logger;
    }

    private function apply_discount_to_cart($spend_rule)
    {
        $discount_type = $spend_rule['discount_type']['value'];

        $discount_amount = $spend_rule['discountAmount']['value'] ?? 0;

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $original_price   = $cart_item['data']->get_price();
            $discounted_price = $original_price;

            if ('fixed' === $discount_type) {
                $discounted_price = max(0, $original_price - $discount_amount);
            } elseif ('percentage' === $discount_type) {
                $discounted_price = $original_price * (1 - $discount_amount / 100);
            }

            $cart_item['data']->set_price($discounted_price);
            $cart_item['leat_discount'] = [
                'original_price' => $original_price,
                'spend_rule_id'  => $spend_rule['id'],
            ];
        }
    }

    private function remove_discount_from_cart($spend_rule)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['leat_discount']) && $cart_item['leat_discount']['spend_rule_id'] === $spend_rule['id']) {
                $cart_item['data']->set_price($cart_item['leat_discount']['original_price']);
                unset($cart_item['leat_discount']);
            }
        }
    }

    /**
     * Add free or discounted products to cart
     */
    private function add_free_or_discounted_products_to_cart($spend_rule)
    {
        $selected_products = $spend_rule['selectedProducts']['value'] ?? [];
        $discount_type     = $spend_rule['discountType']['value'] ?? 'percentage';
        $discount_value    = $spend_rule['discountValue']['value'] ?? 0;

        foreach ($selected_products as $product_id) {
            $product          = wc_get_product($product_id);
            $original_price   = $product->get_price();
            $discounted_price = $this->calculate_discounted_price($original_price, $discount_type, $discount_value);

            // Check if the product is already in the cart.
            $cart_item_key = $this->find_product_in_cart($product_id);

            if ($cart_item_key) {
                // Product is already in the cart, update its price and quantity.
                $this->update_existing_cart_item($cart_item_key, $discounted_price, $spend_rule['id'], $original_price);
            } else {
                // Product is not in the cart, add it.
                $this->add_new_cart_item($product_id, $discounted_price, $spend_rule['id'], $original_price);
            }
        }
    }

    private function calculate_discounted_price($original_price, $discount_type, $discount_value)
    {
        if ('percentage' === $discount_type) {
            return $original_price * (1 - $discount_value / 100);
        } elseif ('fixed' === $discount_type) {
            return max(0, $original_price - $discount_value);
        }
        return $original_price;
    }

    private function find_product_in_cart($product_id)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] === $product_id) {
                return $cart_item_key;
            }
        }
        return false;
    }

    private function update_existing_cart_item($cart_item_key, $discounted_price, $spend_rule_id, $original_price)
    {
        $cart      = WC()->cart;
        $cart_item = $cart->get_cart_item($cart_item_key);

        // Update the price.
        $cart_item['data']->set_price($discounted_price);

        // Add Leat-specific data.
        $cart_item['leat_discounted_product'] = true;
        $cart_item['leat_spend_rule_id']      = $spend_rule_id;
        $cart_item['leat_original_price']     = $original_price;
        $cart_item['leat_discounted_price']   = $discounted_price;

        // Set quantity to 1.
        $cart->set_quantity($cart_item_key, 1);

        // Update the cart item.
        $cart->cart_contents[$cart_item_key] = $cart_item;
    }

    private function add_new_cart_item($product_id, $discounted_price, $spend_rule_id, $original_price)
    {
        WC()->cart->add_to_cart(
            $product_id,
            1,
            0,
            array(),
            array(
                'leat_discounted_product' => true,
                'leat_spend_rule_id'      => $spend_rule_id,
                'leat_original_price'     => $original_price,
                'leat_discounted_price'   => $discounted_price,
            )
        );
    }

    /**
     * Remove free or discounted products from cart
     */
    private function remove_free_or_discounted_products_from_cart($spend_rule)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['leat_discounted_product']) && $cart_item['leat_spend_rule_id'] === $spend_rule['id']) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
    }

    /**
     * Adjust cart item prices
     */
    public function adjust_cart_item_prices($cart)
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            return;
        }

        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['leat_discounted_product'])) {
                $cart_item['data']->set_price($cart_item['leat_discounted_price']);
                // Remove sale price to avoid sale badge.
                $cart_item['data']->set_sale_price('');

                // Ensure quantity is 1 for discounted products.
                if ($cart_item['quantity'] > 1) {
                    WC()->cart->set_quantity($cart_item['key'], 1);
                }
            } elseif (isset($cart_item['leat_discount'])) {
                $cart_item['data']->set_price($cart_item['data']->get_price());
                // Remove sale price to avoid sale badge.
                $cart_item['data']->set_sale_price('');
            }
        }
    }

    public function handle_applied_coupon(string $coupon_code): void
    {
        $coupon = new \WC_Coupon($coupon_code);

        if ($coupon->get_meta('_leat_spend_rule_coupon') === 'true') {
            $spend_rule_id = $coupon->get_meta('_leat_spend_rule_id');
            $spend_rule = $this->spend_rules->get_spend_rule_by_id($spend_rule_id);

            if ($spend_rule) {
                $this->process_spend_rule($spend_rule);
            }
        }
    }

    public function handle_removed_coupon(string $coupon_code): void
    {
        $coupon = new \WC_Coupon($coupon_code);

        if ($coupon->get_meta('_leat_spend_rule_coupon') === 'true') {
            $spend_rule_id = $coupon->get_meta('_leat_spend_rule_id');
            $spend_rule = $this->spend_rules->get_spend_rule_by_id($spend_rule_id);

            if ($spend_rule) {
                $this->remove_spend_rule($spend_rule);
            }
        }
    }

    public function remove_sale_price_for_discounted_products($sale_price, $product)
    {
        $cart = WC()->cart;
        if ($cart) {
            foreach ($cart->get_cart() as $cart_item) {
                if (isset($cart_item['leat_discounted_product']) && $cart_item['product_id'] === $product->get_id()) {
                    return '';
                }
            }
        }
        return $sale_price;
    }

    public function adjust_price_for_discounted_products($price, $product)
    {
        $cart = WC()->cart;
        if ($cart) {
            foreach ($cart->get_cart() as $cart_item) {
                if (isset($cart_item['leat_discounted_product']) && $cart_item['product_id'] === $product->get_id()) {
                    return $cart_item['leat_discounted_price'];
                }
            }
        }
        return $price;
    }


    private function process_spend_rule(array $spend_rule): void
    {
        switch ($spend_rule['type']['value']) {
            case 'FREE_PRODUCT':
                $this->add_free_or_discounted_products_to_cart($spend_rule);
                break;
            case 'FIXED_DISCOUNT':
            case 'PERCENTAGE_DISCOUNT':
                $this->apply_discount_to_cart($spend_rule);
                break;
        }
    }

    private function remove_spend_rule(array $spend_rule): void
    {
        switch ($spend_rule['type']['value']) {
            case 'FREE_PRODUCT':
                $this->remove_free_or_discounted_products_from_cart($spend_rule);
                break;
            case 'FIXED_DISCOUNT':
            case 'PERCENTAGE_DISCOUNT':
                $this->remove_discount_from_cart($spend_rule);
                break;
        }
    }
}
