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
		add_action('woocommerce_order_refunded', [$this, 'handle_order_refunded'], 10, 2);
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

	public function handle_customer_creation($wp_user_id, $new_customer_data)
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
		$this->connection->sync_user_attributes($wp_user_id, $uuid);

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

		$this->connection->sync_user_attributes($user_id, $uuid);
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

		$this->connection->sync_user_attributes($user_id, $uuid);
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

		$this->connection->sync_user_attributes($user_id, $uuid);

		// Apply credits based on PLACE_ORDER earn rule
		$order_total = $order->get_total();
		$applicable_rule = $this->earn_rules->get_applicable_place_order_rule( $order_total );

		if ($applicable_rule) {
			// Note: We don't apply the credits from the rule in WordPress.
			// Instead, this is managed by a rule in the Piggy dashboard.
			$result = $this->connection->apply_credits($uuid, null, $order_total, 'purchase_amount');

			if (!$result) {
				$this->logger->error("Failed to apply credits to user $user_id for order $order_id");

				return;
			}

			$credits = $result->getCredits();
			$result_uuid = $result->getUuid();

			// Save the result UUID in the order meta
			$order->update_meta_data('_piggy_earn_rule_credit_transaction_uuid', $result_uuid);
			// Save the total credits isssued
			$order->update_meta_data('_piggy_earn_rule_credits_issued', $credits);

			$order->save();

			$this->connection->add_reward_log($user_id, $applicable_rule['id'], $credits);
		}
	}

	public function handle_order_refunded($order_id, $refund_id)
	{
		$order = wc_get_order($order_id);
		if (!$order) {
			return;
		}

		$credit_transaction_uuid = $order->get_meta('_piggy_earn_rule_credit_transaction_uuid');
		$original_credits = $order->get_meta('_piggy_earn_rule_credits_issued');

		if (!$credit_transaction_uuid) {
			$this->logger->error("No Piggy credit transaction UUID found for order $order_id");
			return;
		}

		$refund = new \WC_Order_Refund($refund_id);
		$refund_amount = $refund->get_amount();
		$original_amount = $order->get_total();
		$is_full_refund = $refund_amount >= $original_amount;
		$refund_type = $is_full_refund ? "full" : "partial";

		if ($is_full_refund) {
			$result = $this->connection->refund_credits_full($credit_transaction_uuid);

			if($result) {
				$this->logger->info("Successfully processed $refund_type refund for credit transaction UUID $credit_transaction_uuid for order $order_id");
			}
		} else {
			// Partial refunds are not yet supported.
			$this->logger->warn('Partial refunds are not supported yet');
		}
	}
}
