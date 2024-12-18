<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\EarnRules;
use Leat\Domain\Services\SpendRules;
use Leat\Settings;
use Leat\Utils\Logger;
use Leat\Utils\OrderNotes;

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
	 * @var Settings
	 */
	private $settings;

	/**
	 * CustomerSession constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection, EarnRules $earn_rules, SpendRules $spend_rules, Settings $settings)
	{
		$this->connection = $connection;
		$this->earn_rules = $earn_rules;
		$this->spend_rules = $spend_rules;
		$this->settings = $settings;
		$this->logger = new Logger();

		add_action('woocommerce_created_customer', [$this, 'handle_customer_creation'], 10, 3);
		add_action('show_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('edit_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('show_user_profile', [$this, 'show_claimed_rewards_on_profile']);
		add_action('edit_user_profile', [$this, 'show_claimed_rewards_on_profile']);
		add_action('wp_login', [$this, 'sync_attributes_on_login'], 10, 2);
		add_action('wp_logout', [$this, 'sync_attributes_on_logout']);
		add_action('woocommerce_applied_coupon', [$this, 'handle_applied_coupon'], 10, 1);
		add_action('woocommerce_removed_coupon', [$this, 'handle_removed_coupon'], 10, 1);
		add_action('woocommerce_before_calculate_totals', [$this, 'adjust_cart_item_prices'], 10, 1);
		add_filter('woocommerce_product_get_sale_price', [$this, 'remove_sale_price_for_discounted_products'], 10, 2);
		add_filter('woocommerce_product_get_price', [$this, 'adjust_price_for_discounted_products'], 10, 2);
		add_action('woocommerce_order_refunded', [$this, 'handle_order_refunded'], 10, 2);

		// Only apply credits and log rewards when order is fully completed
		add_action('woocommerce_order_status_completed', [$this, 'sync_attributes_on_order_completed'], 10, 1);

		// Initial contact creation and attribute syncing when order is first placed
		add_action('woocommerce_checkout_order_processed', [$this, 'handle_checkout_order_processed'], 10, 1);
	}

	/**
	 * Handle applied coupon
	 */
	public function handle_applied_coupon($coupon_code)
	{
		$coupon = new \WC_Coupon($coupon_code);

		if ($coupon->get_meta('_leat_spend_rule_coupon') === 'true') {
			$spend_rule_id = $coupon->get_meta('_leat_spend_rule_id');
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

	public function remove_sale_price_for_discounted_products($sale_price, $product)
	{
		$cart = WC()->cart;
		if ($cart) {
			foreach ($cart->get_cart() as $cart_item) {
				if (isset($cart_item['leat_discounted_product']) && $cart_item['product_id'] == $product->get_id()) {
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
				if (isset($cart_item['leat_discounted_product']) && $cart_item['product_id'] == $product->get_id()) {
					return $cart_item['leat_discounted_price'];
				}
			}
		}
		return $price;
	}


	/**
	 * Handle removed coupon
	 */
	public function handle_removed_coupon($coupon_code)
	{
		$coupon = new \WC_Coupon($coupon_code);

		if ($coupon->get_meta('_leat_spend_rule_coupon') === 'true') {
			$spend_rule_id = $coupon->get_meta('_leat_spend_rule_id');
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
			$cart_item['leat_discount'] = [
				'original_price' => $original_price,
				'spend_rule_id' => $spend_rule['id'],
			];
		}
	}

	private function remove_discount_from_cart($spend_rule)
	{
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			if (isset($cart_item['leat_discount']) && $cart_item['leat_discount']['spend_rule_id'] == $spend_rule['id']) {
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

		// Add Leat-specific data
		$cart_item['leat_discounted_product'] = true;
		$cart_item['leat_spend_rule_id'] = $spend_rule_id;
		$cart_item['leat_original_price'] = $original_price;
		$cart_item['leat_discounted_price'] = $discounted_price;

		// Set quantity to 1
		$cart->set_quantity($cart_item_key, 1);

		// Update the cart item
		$cart->cart_contents[$cart_item_key] = $cart_item;
    }

    private function add_new_cart_item($product_id, $discounted_price, $spend_rule_id, $original_price)
    {
        WC()->cart->add_to_cart($product_id, 1, 0, array(), array(
            'leat_discounted_product' => true,
            'leat_spend_rule_id' => $spend_rule_id,
            'leat_original_price' => $original_price,
            'leat_discounted_price' => $discounted_price
        ));
    }

    /**
     * Remove free or discounted products from cart
     */
    private function remove_free_or_discounted_products_from_cart($spend_rule)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['leat_discounted_product']) && $cart_item['leat_spend_rule_id'] == $spend_rule['id']) {
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
			if (isset($cart_item['leat_discounted_product'])) {
				$cart_item['data']->set_price($cart_item['leat_discounted_price']);
				// Remove sale price to avoid sale badge
				$cart_item['data']->set_sale_price('');

				// Ensure quantity is 1 for discounted products
				if ($cart_item['quantity'] > 1) {
					WC()->cart->set_quantity($cart_item['key'], 1);
				}
			} elseif (isset($cart_item['leat_discount'])) {
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
			<h3><?php esc_html_e('Leat Claimed Rewards', 'leat-crm'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="leat_claimed_rewards"><?php esc_html_e('Claimed Rewards', 'leat-crm'); ?></label></th>
					<td>
						<?php
						if (!empty($reward_logs)) {
							echo '<ul>';
								foreach ($reward_logs as $log) {
									echo '<li>';
									echo esc_html__('Earn Rule ID: ', 'leat-crm') . esc_html($log['earn_rule_id']) . '<br>';
									echo esc_html__('Credits: ', 'leat-crm') . esc_html($log['credits']) . '<br>';
									echo esc_html__('Timestamp: ', 'leat-crm') . esc_html(gmdate('Y-m-d H:i:s', (int)$log['timestamp']));
									echo '</li>';
								}
							echo '</ul>';
						} else {
							esc_html_e('No claimed rewards.', 'leat-crm');
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
		<h3><?php esc_html_e('Leat', 'leat-crm'); ?></h3>

		<table class="form-table">
			<tr>
				<th><label for="leat_uuid"><?php esc_html_e('Contact ID', 'leat-crm'); ?></label></th>
				<td>
					<?php
					$contact = $this->connection->get_contact($user->ID);
					$uuid = $contact['uuid'];

					echo esc_html($uuid ? $uuid : '—');
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	public function update_last_login($user_id) {
		$last_login = current_time('mysql');
		update_user_meta($user_id, 'leat_last_login', $last_login);
		return $last_login;
	}

	public function sync_attributes_on_login($user_login, $user)
	{
		try {
			$client = $this->connection->init_client();

			if (!$client) {
				$this->logger->error("Failed to initialize client");

				return;
			}

			$user_id = $user->ID;
			$contact = $this->connection->get_contact($user_id);
			$uuid = $contact['uuid'];

			$this->update_last_login($user_id);

			$this->connection->sync_user_attributes($user_id, $uuid);
		} catch (\Throwable $th) {
			$this->logger->error("Error syncing attributes on login: " . $th->getMessage());
		}
	}

	public function sync_attributes_on_logout()
	{
		try {
			$user_id = get_current_user_id();

			if (!$user_id) {
				return;
			}

			$contact = $this->connection->get_contact($user_id);
			$uuid = $contact['uuid'];

			$this->connection->sync_user_attributes($user_id, $uuid);
		} catch (\Throwable $th) {
			$this->logger->error("Error syncing attributes on logout: " . $th->getMessage());
		}
	}

	/**
	 * Only when the order is completed, we sync the attributes and apply credits
	 */
	public function sync_attributes_on_order_completed($order_id)
	{
		try {
			$order = wc_get_order($order_id);
			$user_id = $order->get_user_id();
			$guest_checkout = empty($user_id);

			// Get UUID either from user meta or order meta for guests
			$uuid = null;

			if($guest_checkout) {
				$uuid = $order->get_meta('_leat_contact_uuid');
			} else {
				$contact = $this->connection->get_contact($user_id);
				$uuid = $contact['uuid'];
			}

			if (!$uuid) {
				throw new \Exception("No UUID found for order $order_id");
			}

			// If credits are already issued, we don't need to apply them again
			$credit_transaction_uuid = $order->get_meta('_leat_earn_rule_credit_transaction_uuid');
			if ($credit_transaction_uuid) {
				return;
			}

			if (!$uuid) {
				return;
			}

			// Sync all attributes including order-related data
			if ($guest_checkout) {
				$this->connection->sync_guest_attributes($order, $uuid);
			} else {
				$this->connection->sync_user_attributes($user_id, $uuid);
			}

			// Apply credits based on PLACE_ORDER earn rule
			$order_total = $order->get_total();
			$applicable_rule = $this->earn_rules->get_applicable_place_order_rule($order_total);

			if ($applicable_rule) {
				// Note: We don't apply the credits from the rule in WordPress.
				// Instead, this is managed by a rule in the Leat dashboard.
				$result = $this->connection->apply_credits($uuid, null, $order_total, 'purchase_amount');

				if (!$result) {
					$this->logger->error("Failed to apply credits to user $user_id for order $order_id");
					OrderNotes::addError($order, 'Failed to apply loyalty credits for this order.');
					return;
				}

				$credits = $result->getCredits();
				$result_uuid = $result->getUuid();

				// Save metadata and add order note
				$order->update_meta_data('_leat_earn_rule_credit_transaction_uuid', $result_uuid);
				$order->update_meta_data('_leat_earn_rule_credits_issued', $credits);
				OrderNotes::addSuccess($order, sprintf('Added %d loyalty credits (Transaction ID: %s)', $credits, $result_uuid));
				$order->save();

				if ($user_id) {
					$this->logger->info("Adding $credits credits to user $user_id for order $order_id");

					$this->connection->add_reward_log($user_id, $applicable_rule['id'], $credits);
				}
			}
		} catch (\Throwable $th) {
			$this->logger->error("Error syncing attributes on order completed: " . $th->getMessage());
			OrderNotes::addError($order, 'Error processing loyalty credits: ' . $th->getMessage());
		}
	}

	public function handle_order_refunded($order_id, $refund_id)
	{
		try {
			$order = wc_get_order($order_id);

			if (!$order) {
				return;
			}

			$credit_transaction_uuid = $order->get_meta('_leat_earn_rule_credit_transaction_uuid');
			$original_credits = $order->get_meta('_leat_earn_rule_credits_issued');

			if (!$credit_transaction_uuid) {
				$this->logger->error("No Leat credit transaction UUID found for order $order_id");
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
					OrderNotes::addSuccess($order, sprintf('Refunded %d loyalty credits due to full order refund', $original_credits));
				} else {
					OrderNotes::addError($order, 'Failed to process loyalty credit refund');
				}
			} else {
				// Partial refunds are not yet supported.
				$this->logger->error('Partial refunds are not supported yet');
				OrderNotes::addWarning($order, 'Partial refunds of loyalty credits are not supported');
			}
		} catch (\Throwable $th) {
			$this->logger->error("Error handling order refunded: " . $th->getMessage());
			OrderNotes::addError($order, 'Error processing loyalty credit refund: ' . $th->getMessage());
		}
	}

	/**
	 * Handle order processing for both logged-in and guest users
	 */
	public function handle_checkout_order_processed($order_id)
	{
		try {
			$order = wc_get_order($order_id);
			if (!$order) {
				$this->logger->error("Could not find order with ID: " . $order_id);
				return;
			}

			$user_id = $order->get_user_id();
			$guest_checkout = empty($user_id);

			$this->logger->info('Handling initial checkout processing for order: ' . $order->get_id());

			// Skip if it's a guest checkout and guest users are not included
			if ($guest_checkout) {
				$include_guests = $this->settings->get_setting_by_id('include_guests')['value'] ?? 'off';
				if ($include_guests !== 'on') {
					$this->logger->info("Guest checkout detected but include_guests is disabled. Skipping Leat processing.");
					OrderNotes::addWarning($order, 'Guest loyalty program disabled - no points awarded');
					return;
				}
			}

			$client = $this->connection->init_client();
			if (!$client) {
				$this->logger->error("Failed to initialize client");
				return;
			}

			// Get or create contact UUID
			$uuid = null;
			if ($guest_checkout) {
				$email = $order->get_billing_email();
				if (!$email) {
					$this->logger->error("No email provided for guest order: " . $order->get_id());
					return;
				}

				$contact = $this->connection->create_contact($email);
				if (!$contact) {
					$this->logger->error("Failed to create contact for guest order: " . $order->get_id());
					return;
				}
				$uuid = $contact['uuid'];

				// Store UUID in order meta for future reference
				$order->update_meta_data('_leat_contact_uuid', $uuid);
				$order->save();
			} else {
				$contact = $this->connection->get_contact($user_id);
				$uuid = $contact['uuid'];
			}

			if (!$uuid) {
				$this->logger->error("No UUID found/created for order: " . $order->get_id());
				OrderNotes::addError($order, 'Failed to create/find loyalty account');
				return;
			}

			OrderNotes::addSuccess($order, sprintf('Successfully linked to loyalty account %s', $uuid));

			// Only sync non-order related attributes during checkout
			$this->connection->sync_basic_attributes_from_order($order, $uuid, $guest_checkout);
		} catch (\Throwable $th) {
			$this->logger->error("Error processing checkout order: " . $th->getMessage());
			OrderNotes::addError($order, 'Error processing loyalty account: ' . $th->getMessage());
		}
	}

	/**
	 * Sync order attributes to Leat for both guest and registered users
	 */
	private function sync_order_attributes($order, $uuid)
	{
		try {
			$user_id = $order->get_user_id();
			$guest_checkout = empty($user_id);

			if ($guest_checkout) {
				$this->logger->info('Syncing guest attributes for order: ' . $order->get_id());
				return $this->connection->sync_guest_attributes($order, $uuid);
			} else {
				$this->logger->info('Syncing user attributes for order: ' . $order->get_id());
				return $this->connection->sync_user_attributes($user_id, $uuid);
			}

		} catch (\Throwable $th) {
			$this->logger->error("Error syncing order attributes: " . $th->getMessage());
			return false;
		}
	}
}
