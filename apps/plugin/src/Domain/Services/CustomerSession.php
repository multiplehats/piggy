<?php

namespace PiggyWP\Domain\Services;

use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use PiggyWP\Api\Connection;
use PiggyWP\Domain\Services\EarnRules;
use PiggyWP\Domain\Services\SpendRules;
use PiggyWP\Utils\Logger;

/**
 * Class CustomerSession
 */
class CustomerSession
{
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var EarnRules
	 */
	private $earn_rules;

	/**
	 * @var SpendRules
	 */
	private $spend_rules;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * CustomerSession constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection, EarnRules $earn_rules, SpendRules $spend_rules)
	{
		$this->connection = $connection;
		$this->earn_rules = $earn_rules;
		$this->spend_rules = $spend_rules;
		$this->logger = new Logger();

		add_action('woocommerce_created_customer', [$this, 'handle_customer_creation'], 10, 3);
		add_action('show_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('edit_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('show_user_profile', [$this, 'show_claimed_rewards_on_profile']);
		add_action('edit_user_profile', [$this, 'show_claimed_rewards_on_profile']);
		add_action('wp_login', [$this, 'sync_attributes_on_login'], 10, 2);
		add_action('wp_logout', [$this, 'sync_attributes_on_logout']);
		add_action('woocommerce_order_status_completed', [$this, 'sync_attributes_on_order_completed'], 10, 1);
		add_action('woocommerce_applied_coupon', [$this, 'handle_applied_coupon'], 10, 1);
		add_action('woocommerce_removed_coupon', [$this, 'handle_removed_coupon'], 10, 1);
		add_action('woocommerce_before_calculate_totals', [$this, 'adjust_cart_item_prices'], 10, 1);
		add_filter('woocommerce_product_get_sale_price', [$this, 'remove_sale_price_for_discounted_products'], 10, 2);
		add_filter('woocommerce_product_get_price', [$this, 'adjust_price_for_discounted_products'], 10, 2);
	}

	/**
	 * Handle applied coupon
	 */
	public function handle_applied_coupon($coupon_code)
	{
		$coupon = new \WC_Coupon($coupon_code);

		if ($coupon->get_meta('_piggy_spend_rule_coupon') === 'true') {
			$spend_rule_id = $coupon->get_meta('_piggy_spend_rule_id');
			$spend_rule = $this->spend_rules->get_spend_rule_by_id($spend_rule_id);

			if ($spend_rule) {
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
		}
	}

	/**
	 * Handle removed coupon
	 */
	public function handle_removed_coupon($coupon_code)
	{
		$coupon = new \WC_Coupon($coupon_code);

		if ($coupon->get_meta('_piggy_spend_rule_coupon') === 'true') {
			$spend_rule_id = $coupon->get_meta('_piggy_spend_rule_id');
			$spend_rule = $this->spend_rules->get_spend_rule_by_id($spend_rule_id);

			if ($spend_rule) {
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
	}

	private function apply_discount_to_cart($spend_rule)
	{
		$discount_type = $spend_rule['discount_type']['value'];

		$discount_amount = $spend_rule['discountAmount']['value'] ?? 0;

		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			$original_price = $cart_item['data']->get_price();
			$discounted_price = $original_price;

			if ($discount_type === 'fixed') {
				$discounted_price = max(0, $original_price - $discount_amount);
			} elseif ($discount_type === 'percentage') {
				$discounted_price = $original_price * (1 - $discount_amount / 100);
			}

			$cart_item['data']->set_price($discounted_price);
			$cart_item['piggy_discount'] = [
				'original_price' => $original_price,
				'spend_rule_id' => $spend_rule['id'],
			];
		}
	}

	private function remove_discount_from_cart($spend_rule)
	{
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			if (isset($cart_item['piggy_discount']) && $cart_item['piggy_discount']['spend_rule_id'] == $spend_rule['id']) {
				$cart_item['data']->set_price($cart_item['piggy_discount']['original_price']);
				unset($cart_item['piggy_discount']);
			}
		}
	}

    /**
     * Add free or discounted products to cart
     */
    private function add_free_or_discounted_products_to_cart($spend_rule)
    {
        $selected_products = $spend_rule['selectedProducts']['value'] ?? [];
        $discount_type = $spend_rule['discountType']['value'] ?? 'percentage';
        $discount_value = $spend_rule['discountValue']['value'] ?? 0;

        foreach ($selected_products as $product_id) {
            $product = wc_get_product($product_id);
            $original_price = $product->get_price();
            $discounted_price = $this->calculate_discounted_price($original_price, $discount_type, $discount_value);

            // Check if the product is already in the cart
            $cart_item_key = $this->find_product_in_cart($product_id);

            if ($cart_item_key) {
                // Product is already in the cart, update its price and quantity
                $this->update_existing_cart_item($cart_item_key, $discounted_price, $spend_rule['id'], $original_price);
            } else {
                // Product is not in the cart, add it
                $this->add_new_cart_item($product_id, $discounted_price, $spend_rule['id'], $original_price);
            }
        }
    }

    private function calculate_discounted_price($original_price, $discount_type, $discount_value)
    {
        if ($discount_type === 'percentage') {
            return $original_price * (1 - $discount_value / 100);
        } elseif ($discount_type === 'fixed') {
            return max(0, $original_price - $discount_value);
        }
        return $original_price;
    }

    private function find_product_in_cart($product_id)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                return $cart_item_key;
            }
        }
        return false;
    }

    private function update_existing_cart_item($cart_item_key, $discounted_price, $spend_rule_id, $original_price)
    {
		$cart = WC()->cart;
		$cart_item = $cart->get_cart_item($cart_item_key);

		// Update the price
		$cart_item['data']->set_price($discounted_price);

		// Add Piggy-specific data
		$cart_item['piggy_discounted_product'] = true;
		$cart_item['piggy_spend_rule_id'] = $spend_rule_id;
		$cart_item['piggy_original_price'] = $original_price;
		$cart_item['piggy_discounted_price'] = $discounted_price;

		// Set quantity to 1
		$cart->set_quantity($cart_item_key, 1);

		// Update the cart item
		$cart->cart_contents[$cart_item_key] = $cart_item;
    }

    private function add_new_cart_item($product_id, $discounted_price, $spend_rule_id, $original_price)
    {
        WC()->cart->add_to_cart($product_id, 1, 0, array(), array(
            'piggy_discounted_product' => true,
            'piggy_spend_rule_id' => $spend_rule_id,
            'piggy_original_price' => $original_price,
            'piggy_discounted_price' => $discounted_price
        ));
    }

    /**
     * Remove free or discounted products from cart
     */
    private function remove_free_or_discounted_products_from_cart($spend_rule)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['piggy_discounted_product']) && $cart_item['piggy_spend_rule_id'] == $spend_rule['id']) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
    }

    /**
     * Adjust cart item prices
     */
	public function adjust_cart_item_prices($cart)
	{
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}

		if (did_action('woocommerce_before_calculate_totals') >= 2) {
			return;
		}

		foreach ($cart->get_cart() as $cart_item) {
			if (isset($cart_item['piggy_discounted_product'])) {
				$cart_item['data']->set_price($cart_item['piggy_discounted_price']);
				// Remove sale price to avoid sale badge
				$cart_item['data']->set_sale_price('');

				// Ensure quantity is 1 for discounted products
				if ($cart_item['quantity'] > 1) {
					WC()->cart->set_quantity($cart_item['key'], 1);
				}
			} elseif (isset($cart_item['piggy_discount'])) {
				$cart_item['data']->set_price($cart_item['data']->get_price());
				// Remove sale price to avoid sale badge
				$cart_item['data']->set_sale_price('');
			}
		}
	}

	public function handle_customer_creation($wp_user_id, $new_customer_data, $password_generated)
	{
		$this->logger->info("Handling customer creation for user ID: $wp_user_id");


		$client = $this->connection->init_client();

		if (!$client) {
			$this->logger->error("Failed to initialize client");

			return;
		}

		$email = $new_customer_data['user_email'];

		if (!$email) {
			$this->logger->error("No email provided for user ID: $wp_user_id");

			return;
		}

		$contact = $this->connection->create_contact($email);

		if (!$contact) {
			$this->logger->error("Failed to create contact for user ID: $wp_user_id, email: $email");

			return;
		}

		$uuid = $contact['uuid'];

		$this->logger->info("Created contact with UUID: $uuid for user ID: $wp_user_id");

		$this->connection->update_user_meta_uuid($uuid, $wp_user_id);
		$this->sync_user_attributes($wp_user_id, $uuid);

		$earn_rules = $this->earn_rules->get_earn_rules_by_type('CREATE_ACCOUNT');

		if ($earn_rules) {
			// Here we have at least one earn rule of type 'CREATE_ACCOUNT'. We always grab the first one
			// We check $earnRule['credits']['value'] to see how much credit we should give
			$earn_rule = $earn_rules[0];

			if ($earn_rule['credits']['value'] > 0) {
				$credits = $earn_rule['credits']['value'];

				$this->logger->info("Applying $credits credits to user $wp_user_id");

				$result = $this->connection->apply_credits($uuid, $credits);

				$this->connection->add_reward_log($wp_user_id, $earn_rule['id'], $credits);

				if (!$result) {
					$this->logger->error("Failed to apply $credits credits to user $wp_user_id");
				} else {
					$this->logger->info("Successfully applied $credits credits to user $wp_user_id");
				}
			}
		}
	}

	public function show_claimed_rewards_on_profile($user)
	{
		$reward_logs = $this->connection->get_user_reward_logs($user->ID);

		?>
			<h3>Piggy Claimed Rewards</h3>
			<table class="form-table">
				<tr>
					<th><label for="piggy_claimed_rewards">Claimed Rewards</label></th>
					<td>
						<?php
						if (!empty($reward_logs)) {
							echo '<ul>';
								foreach ($reward_logs as $log) {
									echo '<li>';
									echo 'Earn Rule ID: ' . esc_html($log['earn_rule_id']) . '<br>';
									echo 'Credits: ' . esc_html($log['credits']) . '<br>';
									echo 'Timestamp: ' . esc_html(date('Y-m-d H:i:s', (int)$log['timestamp']));
									echo '</li>';
								}
							echo '</ul>';
						} else {
							echo 'No claimed rewards.';
						}
						?>
					</td>
				</tr>
			</table>
		<?php
	}

	public function show_uuid_on_profile($user)
	{
		?>
		<h3>Piggy</h3>

		<table class="form-table">
			<tr>
				<th><label for="piggy_uuid">Contact ID</label></th>
				<td>
					<?php
					$uuid = $this->connection->get_contact_uuid_by_wp_id($user->ID);

					echo $uuid ? $uuid : '—';
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	public function update_last_login($user_id) {
		$last_login = current_time('mysql');
		update_user_meta($user_id, 'piggy_last_login', $last_login);
		return $last_login;
	}

	public function sync_attributes_on_login($user_login, $user)
	{
		$client = $this->connection->init_client();

		if (!$client) {
			$this->logger->error("Failed to initialize client");

			return;
		}

		$user_id = $user->ID;
		$uuid = $this->connection->get_contact_uuid_by_wp_id($user_id);

		if (!$uuid) {
			$email = $user->user_email;

			if (!$email) {
				return;
			}

			$contact = $this->connection->create_contact($email);

			if (!$contact) {
				return;
			}

			$uuid = $contact['uuid'];

			$this->connection->update_user_meta_uuid($uuid, $user_id);
		}

		$this->update_last_login($user_id);

		$this->sync_user_attributes($user_id, $uuid);
	}

	public function sync_attributes_on_logout()
	{
		$user_id = get_current_user_id();

		if (!$user_id) {
			return;
		}

		$uuid = $this->connection->get_contact_uuid_by_wp_id($user_id);

		if (!$uuid) {
			return;
		}

		$this->sync_user_attributes($user_id, $uuid);
	}

	public function sync_attributes_on_order_completed($order_id)
	{
		$order = wc_get_order($order_id);
		$user_id = $order->get_user_id();

		if (!$user_id) {
			return;
		}

		$uuid = $this->connection->get_contact_uuid_by_wp_id($user_id);

		if (!$uuid) {
			return;
		}

		$this->sync_user_attributes($user_id, $uuid);

		// Apply credits based on PLACE_ORDER earn rule
		$order_total = $order->get_total();
		$applicable_rule = $this->earn_rules->get_applicable_place_order_rule($order_total);

		if ($applicable_rule) {
			// Note: We don't apply the credits from the rule in WordPress.
			// Instead, this is managed by a rule in the Piggy dashboard.
			$result = $this->connection->apply_credits($uuid, null, $order_total, 'purchase_amount');

			if (!$result) {
				$this->logger->error("Failed to apply credits to user $user_id for order $order_id");

				return;
			}

			$credits = $result->getCredits();

			$this->connection->add_reward_log($user_id, $applicable_rule['id'], $credits);
		}
	}

	private function sync_user_attributes($user_id, $uuid)
	{
		try {
			$user = get_userdata($user_id);

			if (!$user) {
				throw new \Exception('User not found');
			}

			$attributes = $this->get_user_attributes($user_id);

			$update_result = $this->connection->update_contact($uuid, $attributes);

			return $update_result;
		} catch (\Exception $e) {
			$this->logger->error("Failed to sync user attributes: " . $e->getMessage());

			return false;
		}
	}

	/**
	 * Get user attributes for Piggy.
	 *
	 * @param int $user_id
	 * @return array
	 */
	protected function get_user_attributes($user_id)
	{
		$user = get_userdata($user_id);
		$attributes = [
			'wp_user_id' => $user_id,
			'firstname' => $user->first_name,
			'lastname' => $user->last_name,
			'wp_user_role' => implode(', ', $user->roles),
			'wp_account_age_days' => floor((time() - strtotime($user->user_registered)) / (60 * 60 * 24)),
			'wp_last_login' => get_user_meta($user_id, 'piggy_last_login', true) ?: '',
			'wp_post_count' => count_user_posts($user_id),
		];

		$wc_attributes = $this->get_woocommerce_user_data($user_id);

		$attributes = array_merge($attributes, $wc_attributes);

		$this->ensure_custom_attributes_exist();

		return $attributes;
	}

	/**
	 * Ensure custom attributes exist in Piggy.
	 */
	public function ensure_custom_attributes_exist()
	{
		$client = $this->connection->init_client();

		if (!$client) {
			$this->logger->error("Failed to initialize client");

			return;
		}

		$attributes_list = CustomAttribute::list(["entity" => "contact"]);

		$currency = strtolower(get_woocommerce_currency());

		$required_attributes = [
			["name" => "wp_user_id", "label" => "WordPress User ID", "type" => "number"],
			["name" => "wp_user_role", "label" => "WordPress User Role", "type" => "text"],
			["name" => "wp_account_age_days", "label" => "WordPress Account Age (Days)", "type" => "number"],
			["name" => "wp_last_login", "label" => "WordPress Last Login", "type" => "date_time"],
			["name" => "wp_post_count", "label" => "WordPress Post Count", "type" => "number"],
			["name" => "wp_wc_total_spent_" . $currency, "label" => "WooCommerce Total Spent (" . strtoupper($currency) . ")", "type" => "float"],
			["name" => "wp_wc_orders_count", "label" => "WooCommerce Orders Count", "type" => "number"],
			["name" => "wp_create_date", "label" => "WordPress Create Date", "type" => "date_time"],
			["name" => "wp_wc_last_order_amount_" . $currency, "label" => "WooCommerce Last Order Amount (" . strtoupper($currency) . ")", "type" => "float"],
			["name" => "wp_wc_last_order_date", "label" => "WooCommerce Last Order Date", "type" => "date_time"],
			["name" => "wp_wc_average_order_value_" . $currency, "label" => "WooCommerce Average Order Value (" . strtoupper($currency) . ")", "type" => "float"],
			["name" => "wp_wc_first_order_date", "label" => "WooCommerce First Order Date", "type" => "date_time"],
			["name" => "wp_wc_product_categories_purchased", "label" => "WooCommerce Product Categories Purchased", "type" => "multi_select"],
			["name" => "wp_wc_total_products_purchased", "label" => "WooCommerce Total Products Purchased", "type" => "number"],
		];

		foreach($required_attributes as $attr) {
			if (!$this->attribute_exists($attributes_list, $attr['name'])) {
				$attribute_data = [
					"entity" => "contact",
					"name" => $attr['name'],
					"label" => $attr['label'],
					"type" => $attr['type']
				];

				if ($attr['name'] === 'wp_wc_product_categories_purchased') {
					$attribute_data['options'] = $this->get_product_categories_options();
				}

				CustomAttribute::create($attribute_data);
			}
		}
	}

	private function attribute_exists($attributes_list, $name) {
		foreach ($attributes_list as $attribute) {
			if ($attribute->getName() === $name) {
				return true;
			}
		}
		return false;
	}

	private function get_product_categories_options()
	{
		$categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
		$options = [];

		foreach ($categories as $category) {
			$options[] = [
				'label' => $category->name,
				'value' => $category->term_id
			];
		}

		return $options;
	}

	/**
	 * Get WooCommerce user data.
	 *
	 * @param int $user_id
	 * @return array
	 */
	private function get_woocommerce_user_data($user_id) {
		if (!function_exists('wc_get_customer_total_spent') || !function_exists('wc_get_customer_order_count')) {
			return $this->get_default_wc_attributes();
		}

		$user = get_user_by('id', $user_id);
		if (!$user) {
			return $this->get_default_wc_attributes();
		}

		$total_spent = wc_get_customer_total_spent($user_id);
		$orders_count = wc_get_customer_order_count($user_id);
		$create_date = $user->user_registered;

		$customer_orders = wc_get_orders(array(
			'customer' => $user_id,
			'limit' => 1,
			'orderby' => 'date',
			'order' => 'DESC',
		));

		$last_order_amount = 0;
		$last_order_date = '';

		if (!empty($customer_orders)) {
			$last_order = $customer_orders[0];
			$last_order_amount = $last_order->get_total();
			$last_order_date = $last_order->get_date_created()->format('Y-m-d H:i:s');
		}

		$currency = strtolower(get_woocommerce_currency());
		$first_order_date = $this->get_first_order_date($user_id);

		return [
			'wp_wc_total_spent_' . $currency => (float)$total_spent,
			'wp_wc_orders_count' => (int)$orders_count,
			'wp_create_date' => $create_date,
			'wp_wc_last_order_amount_' . $currency => (float)$last_order_amount,
			'wp_wc_last_order_date' => $last_order_date,
			'wp_wc_average_order_value_' . $currency => $orders_count > 0 ? round($total_spent / $orders_count, 2) : 0,
			'wp_wc_first_order_date' => $first_order_date,
			'wp_wc_product_categories_purchased' => $this->get_purchased_categories($user_id),
			'wp_wc_total_products_purchased' => $this->get_total_products_purchased($user_id),
		];
	}

	private function get_default_wc_attributes() {
		$currency = strtolower(get_woocommerce_currency());
		return [
			'wp_wc_total_spent_' . $currency => 0,
			'wp_wc_orders_count' => 0,
			'wp_create_date' => '',
			'wp_wc_last_order_amount_' . $currency => 0,
			'wp_wc_last_order_date' => '',
			'wp_wc_average_order_value_' . $currency => 0,
			'wp_wc_first_order_date' => '',
			'wp_wc_product_categories_purchased' => [],
			'wp_wc_total_products_purchased' => 0,
		];
	}

	private function get_first_order_date($user_id) {
		$customer_orders = wc_get_orders(array(
			'customer' => $user_id,
			'limit' => 1,
			'orderby' => 'date',
			'order' => 'ASC',
		));

		if (!empty($customer_orders)) {
			$first_order = $customer_orders[0];
			return $first_order->get_date_created()->format('Y-m-d H:i:s');
		}

		return '';
	}

	private function get_purchased_categories($user_id)
	{
		$categories = array();
		$customer_orders = wc_get_orders(array('customer' => $user_id));

		foreach ($customer_orders as $order) {
			foreach ($order->get_items() as $item) {
				$product = $item->get_product();
				if ($product) {
					$product_categories = $product->get_category_ids();
					$categories = array_merge($categories, $product_categories);
				}
			}
		}

		return array_unique($categories);
	}

	private function get_total_products_purchased($user_id) {
		$total_products = 0;
		$customer_orders = wc_get_orders(array('customer' => $user_id));

		foreach ($customer_orders as $order) {
			foreach ($order->get_items() as $item) {
				$total_products += $item->get_quantity();
			}
		}

		return $total_products;
	}


	public function remove_sale_price_for_discounted_products($sale_price, $product)
	{
		$cart = WC()->cart;
		if ($cart) {
			foreach ($cart->get_cart() as $cart_item) {
				if (isset($cart_item['piggy_discounted_product']) && $cart_item['product_id'] == $product->get_id()) {
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
				if (isset($cart_item['piggy_discounted_product']) && $cart_item['product_id'] == $product->get_id()) {
					return $cart_item['piggy_discounted_price'];
				}
			}
		}
		return $price;
	}
}
