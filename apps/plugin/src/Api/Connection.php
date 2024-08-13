<?php
namespace PiggyWP\Api;

use Exception;
use Piggy\Api\RegisterClient;
use Piggy\Api\ApiClient;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use Piggy\Api\Models\Shops\Shop;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;

class Connection {
	/**
	 * Piggy Register Client instance.
	 *
	 * @var RegisterClient
	 */
	protected $client;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$api_key = $this->get_api_key();

		if( $api_key ) {
			$this->client = new RegisterClient($api_key);
		} else {
			$this->client = null;
		}
	}

	/**
	 * Get the Piggy API key.
	 *
	 * @return string|null The Piggy API key.
	 */
	public function get_api_key() {
		$api_key = get_option('piggy_api_key', null);

		return $api_key;
	}

	/**
	 * Get the Piggy Register Client instance.
	 *
	 * @return null|true
	 */
	public function init_client() {
		$api_key = $this->get_api_key();

		if( $api_key ) {
			ApiClient::configure($api_key, "https://api.piggy.eu");

			return $this->client = true;
		} else {
			return $this->client = null;
		}
	}

	/**
	 * Get the Contacts response.
	 *
	 * @param Contact $contact The contact object.
	 *
	 * @return array
	 */
	private function format_contact( Contact $contact ) {
		$subscriptions = $contact->getSubscriptions();
		$subscription_list = array();

		if( $subscriptions ) {
			foreach( $subscriptions as $subscription ) {
				$type = $subscription->getSubscriptionType();

				$subscription_list[] = [
					'is_subscribed' => $subscription->isSubscribed(),
					'status' => $subscription->getStatus(),
					'type' => [
						'uuid' => $type->getUuid(),
						'name' =>  $type->getName(),
						'description' => $type->getDescription(),
						'active' => $type->isActive(),
						'strategy' => $type->getStrategy(),
					]
				];
			}
		}

		return [
			'uuid' => $contact->getUuid(),
			'email' => $contact->getEmail(),
			'subscriptions' => isset( $subscription_list ) ? $subscription_list : [],
			'attributes' =>  $contact->getCurrentValues(),
			'balance' => [
				'prepaid' => $contact->getPrepaidBalance()->getBalanceInCents(),
				'credits'  => $contact->getCreditBalance()->getBalance(),
			]
		];
	}

	/**
	 * Get a contact.
	 *
	 * @param string $id The contact ID.
	 *
	 * @return array|null
	 */
	public function get_contact( string $id ) {
		$client = $this->init_client();

		if( ! $client ) {
			return null;
		}

		$contact = Contact::get( $id );

		if( ! $contact ) {
			return null;
		}

		return $this->format_contact( $contact );
	}

	/**
	 * Create a new contact.
	 *
	 * @param string $email The contact email.
	 *
	 * @return array|null
	 */
	public function create_contact( string $email ) {
		$client = $this->init_client();

		if( ! $client ) {
			return null;
		}

		$contact = Contact::findOrCreate( array( 'email' => $email ) );

		if( ! $contact ) {
			return null;
		}

		if( is_array( $contact ) && $contact['data']['status'] !== 200 ) {
			return null;
		}

		return $this->format_contact( $contact );
	}

	/**
	 * Update a contact.
	 *
	 * @param string $id The contact ID.
	 * @param array $attributes The contact attributes.
	 *
	 * @return array|null
	 */
	public function update_contact( string $id, array $attributes ) {
		$client = $this->init_client();

		if( ! $client ) {
			return null;
		}

		$contact = Contact::get( $id );

		if( ! $contact ) {
			return null;
		}

		$contact = Contact::update( $id, [ "attributes" => $attributes ] );

		if( ! $contact ) {
			return null;
		}

		return $this->format_contact( $contact );
	}

	private function attribute_exists($attributes_list, $name) {
		foreach ($attributes_list as $attribute) {
			if ($attribute->getName() === $name) {
				return true;
			}
		}
		return false;
	}

	private function getAttributeLabel($name) {
		$labels = [
			"wp_user_id" => "WordPress User ID",
			"wp_wc_total_spent" => "WooCommerce Total Spent",
			"wp_wc_orders_count" => "WooCommerce Orders Count",
			"wp_create_date" => "WordPress Create Date",
			"wp_wc_last_order_amount" => "WooCommerce Last Order Amount",
			"wp_wc_last_order_date" => "WooCommerce Last Order Date"
		];
		return $labels[$name] ?? ucfirst(str_replace('_', ' ', $name));
	}

	private function get_shop_currency() {
		if (function_exists('get_woocommerce_currency')) {
			return get_woocommerce_currency();
		}
		return 'EUR'; // Default to EUR if WooCommerce is not active
	}

	private function get_wordpress_user_data($user_id) {
		if (!function_exists('wc_get_customer_total_spent') || !function_exists('wc_get_customer_order_count')) {
			return [
				'total_spent' => 0,
				'orders_count' => 0,
				'create_date' => '',
				'last_order_amount' => 0,
				'last_order_date' => '',
			];
		}

		$user = get_user_by('id', $user_id);
		if (!$user) {
			return null;
		}

		$total_spent = wc_get_customer_total_spent($user_id);
		$orders_count = wc_get_customer_order_count($user_id);
		$create_date = $user->user_registered;

		// Get the last order
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

		return [
			'total_spent' => (float)$total_spent,
			'orders_count' => (int)$orders_count,
			'create_date' => $create_date,
			'last_order_amount' => (float)$last_order_amount,
			'last_order_date' => $last_order_date,
		];
	}

	/**
	 * Get the Shops response.
	 *
	 * @param Shop $shop The shop object.
	 *
	 * @return array
	 */
	private function format_shop( Shop $shop ) {
		return [
			'uuid' => $shop->getUuid(),
			'name' => $shop->getName(),
		];
	}

	/**
	 * Get the shops.
	 *
	 * @return array|null
	 */
	public function get_shops() {
		$client = $this->init_client();

		if( ! $client ) {
			return null;
		}

		$results = Shop::list();

		if( ! $results ) {
			return null;
		}

		$shops = array();

		foreach( $results as $shop ) {
			$shops[] = $this->format_shop( $shop );
		}

		return $shops;
	}

	/**
	 * Get the shop.
	 *
	 * @param string $id The shop ID.
	 *
	 * @return array|null
	 */
	public function get_shop( string $id ) {
		$client = $this->init_client();

		if( ! $client ) {
			return null;
		}

		$shop = Shop::get( $id );

		if( ! $shop ) {
			return null;
		}

		return $this->format_shop( $shop );
	}

	/**
	 * Get the Rewards response.
	 *
	 * @param Reward $reward The reward object.
	 *
	 * @return array
	 */
	public function format_reward( Reward $reward ) {
		return [
			'uuid' => $reward->getUuid(),
			'title' => $reward->getTitle(),
			'requiredCredits' => $reward->getRequiredCredits(),
			'type' => $reward->getRewardType(),
			'active' => $reward->isActive(),
			'attributes' => $reward->getAttributes(),
		];
	}

	/**
	 * Get the rewards.
	 *
	 * @return array|null
	 */
	public function get_rewards() {
		$client = $this->init_client();

		if( ! $client ) {
			return null;
		}

		$results = Reward::list();

		if( ! $results ) {
			return null;
		}

		$rewards = array();

		foreach( $results as $reward ) {
			$rewards[] = $this->format_reward( $reward );
		}

		return $rewards;
	}

	public function apply_credits( string $contact_uuid, int $credits ) {
		$client = $this->init_client();

		if( ! $client ) {
			return false;
		}

		$shop_uuid = get_option('piggy_shop_uuid', null);

		if ( ! $shop_uuid ) {
			return false;
		}

		$reception = CreditReception::create( [
			'shop_uuid' => $shop_uuid,
			'contact_uuid' => $contact_uuid,
			'credits' => $credits,
		] );

		if( ! $reception ) {
			return false;
		}

		return $reception;
	}

	/**
	 * Get the contact UUID by WordPress user ID.
	 *
	 * @param int $wp_id
	 * @return string|null
	 */
	public function get_contact_uuid_by_wp_id($wp_id)
	{
		$uuid = get_user_meta( $wp_id, 'piggy_uuid', true);

		if( ! $uuid ) {
			return null;
		}

		return $uuid;
	}

	/**
	 * Set the user meta for the Piggy UUID.
	 *
	 * @param string $uuid
	 * @param int $wp_id
	 * @return bool
	 */
	public function update_user_meta_uuid($uuid, $wp_user_id)
	{
		return update_user_meta($wp_user_id, 'piggy_uuid', $uuid);
	}

	/**
	 * Get all user metadata regarding piggy_
	 *
	 * @param int $wp_user_id
	 * @return array
	 */
	public function get_user_piggy_metadata($wp_user_id)
	{
		$meta_data = get_user_meta($wp_user_id);

		$piggy_meta_data = array_filter($meta_data, function($key) {
			return strpos($key, 'piggy_') === 0;
		}, ARRAY_FILTER_USE_KEY);

		return $piggy_meta_data;
	}

	/**
	 * Get user reward logs
	 *
	 * @param int $wp_user_id
	 * @return array
	 */
	public function get_user_reward_logs($wp_user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'piggy_reward_logs';

		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE wp_user_id = %d", $wp_user_id);
		$reward_logs = $wpdb->get_results($query, ARRAY_A);

		return $reward_logs;
	}

	/**
	 * Add an entry to the user's reward logs
	 *
	 * @param int $wp_user_id
	 * @param int $earn_rule_id
	 * @param int $credits
	 * @return bool
	 */
	public function add_reward_log($wp_user_id, $earn_rule_id, $credits) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'piggy_reward_logs';

		$reward_log = [
			'wp_user_id' => $wp_user_id,
			'earn_rule_id' => $earn_rule_id,
			'credits' => $credits,
			'timestamp' => current_time('mysql', 1),
		];

		$inserted = $wpdb->insert($table_name, $reward_log);

		return $inserted !== false;
	}

	/**
	 * Sync user attributes with Piggy.
	 *
	 * @param int $user_id
	 * @param string $uuid
	 * @return bool|array
	 */
	public function sync_user_attributes($user_id, $uuid)
	{
		try {
			$user = get_userdata($user_id);

			if (!$user) {
				return false;
			}

			$attributes = $this->get_user_attributes($user_id);

			$update_result = $this->update_contact($uuid, $attributes);

			return $update_result;
		} catch (Exception $e) {
			error_log("Exception in sync_user_attributes: " . $e->getMessage());
			error_log("Stack trace: " . $e->getTraceAsString());
			return false;
		}
	}

	/**
	 * Get user attributes for Piggy.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_attributes($user_id)
	{
		try {
			$user = get_userdata($user_id);
			$attributes = [
				'wp_user_id' => $user_id,
				'firstname' => $user->first_name,
				'lastname' => $user->last_name,
			];

			$wc_attributes = $this->get_woocommerce_user_data($user_id);

			$attributes = array_merge($attributes, $wc_attributes);

			$this->ensure_custom_attributes_exist();

			return $attributes;
		} catch (Exception $e) {
			error_log("Exception in get_user_attributes: " . $e->getMessage());
			error_log("Stack trace: " . $e->getTraceAsString());
			return [];
		}
	}

	/**
	 * Get WooCommerce user data.
	 *
	 * @param int $user_id
	 * @return array
	 */
	private function get_woocommerce_user_data($user_id) {
		if (!function_exists('wc_get_customer_total_spent') || !function_exists('wc_get_customer_order_count')) {
			return [
				'wp_wc_total_spent_' . strtolower(get_woocommerce_currency()) => 0,
				'wp_wc_orders_count' => 0,
				'wp_create_date' => '',
				'wp_wc_last_order_amount_' . strtolower(get_woocommerce_currency()) => 0,
				'wp_wc_last_order_date' => '',
			];
		}

		$user = get_user_by('id', $user_id);
		if (!$user) {
			return null;
		}

		$total_spent = wc_get_customer_total_spent($user_id);
		$orders_count = wc_get_customer_order_count($user_id);
		$create_date = $user->user_registered;

		// Get the last order
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

		return [
			'wp_wc_total_spent_' . $currency => (float)$total_spent,
			'wp_wc_orders_count' => (int)$orders_count,
			'wp_create_date' => $create_date,
			'wp_wc_last_order_amount_' . $currency => (float)$last_order_amount,
			'wp_wc_last_order_date' => $last_order_date,
		];
	}

	/**
	 * Ensure custom attributes exist in Piggy.
	 */
	public function ensure_custom_attributes_exist()
	{
		$client = $this->init_client();

		if (!$client) {
			return;
		}

		$attributes_list = CustomAttribute::list(["entity" => "contact"]);

		// Get the shop currency
		$currency = get_woocommerce_currency();

		$required_attributes = [
				[
					"name" => "wp_user_id",
					"label" => "WordPress User ID",
					"type" => "number"
				],
				[
					"name" => "wp_wc_total_spent_" . strtolower($currency),
					"label" => "WooCommerce Total Spent (" . $currency . ")",
					"type" => "float"
				],
				[
					"name" => "wp_wc_orders_count",
					"label" => "WooCommerce Orders Count",
					"type" => "number"
				],
				[
					"name" => "wp_create_date",
					"label" => "WordPress Create Date",
					"type" => "date_time"
				],
				[
					"name" => "wp_wc_last_order_amount_" . strtolower($currency),
					"label" => "WooCommerce Last Order Amount (" . $currency . ")",
					"type" => "float"
				],
				[
					"name" => "wp_wc_last_order_date",
					"label" => "WooCommerce Last Order Date",
					"type" => "date_time"
				]
			];

		foreach($required_attributes as $attr) {
			if (!$this->attribute_exists($attributes_list, $attr['name'])) {
				CustomAttribute::create([
					"entity" => "contact",
					"name" => $attr['name'],
					"label" => $attr['label'],
					"type" => $attr['type']
				]);
			}
		}
	}
}
