<?php

namespace Leat\Domain\Services\Cart;

use Leat\Domain\Services\SpendRules;
use Leat\Utils\Logger;

/**
 * Manages cart operations including discounts, free products, and price adjustments.
 *
 * Handles the application and removal of spend rules, cart item management,
 * and price calculations for the Leat plugin's cart functionality.
 */
class CartManager
{
    /**
     * SpendRules service instance.
     *
     * @var SpendRules
     */
    private $spend_rules;

    /**
     * Logger service instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Initializes the cart manager with required dependencies.
     *
     * @param SpendRules $spend_rules SpendRules service instance.
     * @param Logger     $logger      Logger service instance.
     */
    public function __construct(SpendRules $spend_rules, Logger $logger)
    {
        $this->spend_rules = $spend_rules;
        $this->logger = $logger;

        // Register REST API hooks for cart item removal
        add_filter('rest_pre_dispatch', [$this, 'handle_store_api_cart_update'], 10, 3);
    }

    /**
     * Applies discount to all items in the cart based on spend rule.
     *
     * @param array $spend_rule The spend rule configuration array.
     */
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

    /**
     * Removes previously applied discount from cart items.
     *
     * @param array $spend_rule The spend rule configuration array.
     */
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
     * Adds free or discounted products to the cart based on spend rule.
     *
     * @param array $spend_rule The spend rule configuration array.
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

    /**
     * Calculates the discounted price based on discount type and value.
     *
     * @param float  $original_price Original product price.
     * @param string $discount_type  Type of discount (percentage or fixed).
     * @param float  $discount_value Discount amount.
     * @return float Calculated discounted price.
     */
    private function calculate_discounted_price($original_price, $discount_type, $discount_value)
    {
        if ('percentage' === $discount_type) {
            return $original_price * (1 - $discount_value / 100);
        } elseif ('fixed' === $discount_type) {
            return max(0, $original_price - $discount_value);
        }
        return $original_price;
    }

    /**
     * Searches for a product in the cart and returns its key if found.
     *
     * @param int $product_id WooCommerce product ID.
     * @return string|false Cart item key if found, false otherwise.
     */
    private function find_product_in_cart($product_id)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] === $product_id) {
                return $cart_item_key;
            }
        }
        return false;
    }

    /**
     * Updates an existing cart item with new price and metadata.
     *
     * @param string $cart_item_key    Cart item key.
     * @param float  $discounted_price New discounted price.
     * @param string $spend_rule_id    Associated spend rule ID.
     * @param float  $original_price   Original product price.
     */
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

    /**
     * Adds a new item to the cart with discount information.
     *
     * @param int    $product_id       WooCommerce product ID.
     * @param float  $discounted_price Calculated discounted price.
     * @param string $spend_rule_id    Associated spend rule ID.
     * @param float  $original_price   Original product price.
     */
    private function add_new_cart_item($product_id, $discounted_price, $spend_rule_id, $original_price)
    {
        $cart_item_key = WC()->cart->add_to_cart(
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
     * Retrieves coupon code associated with a spend rule.
     *
     * @param string $spend_rule_id Spend rule ID.
     * @return string|null Coupon code if found, null otherwise.
     */
    private function get_coupon_code_by_spend_rule($spend_rule_id)
    {
        foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
            $coupon = new \WC_Coupon($coupon_code);
            if (
                $coupon->get_meta('_leat_spend_rule_coupon') === 'true'
                && $coupon->get_meta('_leat_spend_rule_id') === $spend_rule_id
            ) {
                return $coupon_code;
            }
        }
        return null;
    }

    /**
     * Removes free or discounted products from cart based on spend rule.
     *
     * @param array $spend_rule The spend rule configuration array.
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
     * Handles Store API cart update requests.
     *
     * @param mixed           $response The original response.
     * @param WP_REST_Server $server   REST API server instance.
     * @param WP_REST_Request $request  Request object.
     * @return mixed Modified response.
     */
    public function handle_store_api_cart_update($response, $server, $request)
    {
        $route = $request->get_route();

        if (!function_exists('WC') || !WC()->cart) {
            wc_load_cart();
        }

        // Handle coupon removal
        if (strpos($route, '/wc/store/v1/cart/remove-coupon') !== false) {
            $coupon_code = $request->get_param('code');
            if ($coupon_code) {
                $this->handle_removed_coupon($coupon_code);
            }
        }

        return $response;
    }

    /**
     * Processes actions when a coupon is applied.
     *
     * @param string $coupon_code Applied coupon code.
     */
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

    /**
     * Processes actions when a coupon is removed.
     *
     * @param string $coupon_code Removed coupon code.
     */
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

    /**
     * Removes sale price display for discounted products.
     *
     * @param string       $sale_price Sale price string.
     * @param WC_Product  $product    Product object.
     * @return string Modified sale price string.
     */
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

    /**
     * Adjusts displayed price for discounted products.
     *
     * @param string      $price   Price string.
     * @param WC_Product $product Product object.
     * @return string|float Modified price.
     */
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

    /**
     * Processes spend rule application based on rule type.
     *
     * @param array $spend_rule The spend rule configuration array.
     */
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

    /**
     * Removes spend rule effects based on rule type.
     *
     * @param array $spend_rule The spend rule configuration array.
     */
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

    /**
     * Adjusts cart item prices during total calculation.
     *
     * @param WC_Cart $cart WooCommerce cart object.
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
}
