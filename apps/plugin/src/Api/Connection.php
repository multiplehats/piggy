<?php
namespace Leat\Api;

use Piggy\Api\RegisterClient;
use Piggy\Api\ApiClient;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use Piggy\Api\Models\Shops\Shop;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\Models\Loyalty\Receptions\RewardReception;
use Leat\Domain\Services\SpendRules;
use Leat\Utils\Logger;

class Connection {
	/**
	 * Register Client instance.
	 *
	 * @var RegisterClient
	 */
	protected $client;

	/**
	 * SpendRules service instance.
	 *
	 * @var SpendRules
	 */
	protected $spend_rules_service;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	protected $logger;

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

		$this->spend_rules_service = new SpendRules();
		$this->logger = new Logger();
	}

	/**
	 * Get the Leat API key.
	 *
	 * @return string|null The Leat API key.
	 */
	public function get_api_key() {
		$api_key = get_option('leat_api_key', null);

		if(!$api_key) {
			$api_key = get_option('piggy_api_key', null);
		}

		return $api_key;
	}

	public function has_api_key() {
		$api_key = $this->get_api_key();

		return $api_key !== null && $api_key !== '';
	}

	/**
	 * Get the  Register Client instance.
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

		if (!$client) {
			return null;
		}

		$contact = Contact::get($id);

		if (!$contact) {
			return null;
		}

		$contact = Contact::update($id, ["attributes" => $attributes]);

		if (!$contact) {
			return null;
		}

		return $this->format_contact($contact);
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
		$media_obj = $reward->getMedia();
		$media = $media_obj ? ['type' => $media_obj->getType(), 'value' => $media_obj->getValue()] : null;

		return [
			'uuid' => $reward->getUuid(),
			'title' => $reward->getTitle(),
			'requiredCredits' => $reward->getRequiredCredits(),
			'type' => $reward->getRewardType(),
			'active' => $reward->isActive(),
			'attributes' => $reward->getAttributes(),
			'media' => $media,
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

	public function apply_credits(string $contact_uuid, ?int $credits = null, ?float $unit_value = null, ?string $unit_name = null) {
		$client = $this->init_client();
		if (!$client) return false;

		$shop_uuid = get_option('leat_shop_uuid', null);
		if (!$shop_uuid) return false;

		$params = [
			'shop_uuid' => $shop_uuid,
			'contact_uuid' => $contact_uuid,
		];

		if ($credits !== null) {
			$params['credits'] = $credits;
		}

		if ($unit_value !== null) {
			$params['unit_value'] = $unit_value;
		}

		if ($unit_name !== null) {
			$params['unit_name'] = $unit_name;
		}

		// Ensure that either credits or unit_value is set
		if (!isset($params['credits']) && !isset($params['unit_value'])) {
			return false;
		}

		$reception = CreditReception::create($params);

		return $reception ?: false;
	}

	/**
	 * Get the contact UUID by WordPress user ID.
	 *
	 * @param int $wp_id
	 * @return string|null
	 */
	public function get_contact_uuid_by_wp_id($wp_id, $create = false)
	{
		$uuid = get_user_meta( $wp_id, 'leat_uuid', true);

		if( ! $uuid && $create ) {
			$contact = $this->create_contact( get_the_author_meta( 'email', $wp_id ) );
			$uuid = $contact['uuid'];

			$this->sync_user_attributes($wp_id, $uuid);

			return $uuid;
		}

		return $uuid;
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



	/**
	 * Get user attributes for Leat.
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
			'wp_last_login' => get_user_meta($user_id, 'leat_last_login', true) ?: '',
			'wp_post_count' => count_user_posts($user_id),
		];

		$wc_attributes = $this->get_woocommerce_user_data($user_id);

		$attributes = array_merge($attributes, $wc_attributes);

		$this->ensure_custom_attributes_exist();

		return $attributes;
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
	 * Ensure custom attributes exist in Leat.
	 */
	public function ensure_custom_attributes_exist()
	{
		$client = $this->init_client();

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

	public function sync_user_attributes($user_id, $uuid)
	{
		try {
			$user = get_userdata($user_id);

			if (!$user) {
				throw new \Exception('User not found');
			}

			$attributes = $this->get_user_attributes($user_id);

			$update_result = $this->update_contact($uuid, $attributes);

			return $update_result;
		} catch (\Exception $e) {
			$this->logger->error("Failed to sync user attributes: " . $e->getMessage());

			return false;
		}
	}

	/**
	 * Set the user meta for the Leat UUID.
	 *
	 * @param string $uuid
	 * @param int $wp_id
	 * @return bool
	 */
	public function update_user_meta_uuid($uuid, $wp_user_id)
	{
		return update_user_meta($wp_user_id, 'leat_uuid', $uuid);
	}

	/**
	 * Get all user metadata regarding leat_
	 *
	 * @param int $wp_user_id
	 * @return array
	 */
	public function get_user_leat_metadata($wp_user_id)
	{
		$meta_data = get_user_meta($wp_user_id);

		$leat_meta_data = array_filter($meta_data, function($key) {
			return strpos($key, 'leat_') === 0;
		}, ARRAY_FILTER_USE_KEY);

		return $leat_meta_data;
	}

	/**
	 * Get user reward logs
	 *
	 * @param int $wp_user_id
	 * @return array
	 */
	public function get_user_reward_logs($wp_user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'leat_reward_logs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leat_reward_logs WHERE wp_user_id = %d",
			$wp_user_id
		);

		$cache_key = 'leat_reward_logs_' . $wp_user_id;
		$reward_logs = wp_cache_get($cache_key);

		if (false === $reward_logs) {
			$reward_logs = $wpdb->get_results($query, ARRAY_A);
			wp_cache_set($cache_key, $reward_logs, '', 3600);
		}
		// phpcs:enable

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
		$table_name = $wpdb->prefix . 'leat_reward_logs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$data = [
			'wp_user_id' => $wp_user_id,
			'earn_rule_id' => $earn_rule_id,
			'credits' => $credits,
			'timestamp' => current_time('mysql', 1),
		];

		$format = [
			'%d', // wp_user_id
			'%d', // earn_rule_id
			'%d', // credits
			'%s', // timestamp
		];

		$inserted = $wpdb->insert($table_name, $data, $format);
		// phpcs:enable

		// Clear cache after inserting new log
		wp_cache_delete('leat_reward_logs_' . $wp_user_id);

		return $inserted !== false;
	}

	public function sync_rewards_with_spend_rules() {
		$client = $this->init_client();
		if (!$client) {
			$this->logger->error("Failed to initialize client for reward sync");
			return false;
		}

		$rewards = $this->get_rewards();
		if (!$rewards) {
			$this->logger->error("Failed to retrieve rewards from Leat");
			return false;
		}

		$this->logger->info("Starting reward sync. Total rewards retrieved: " . count($rewards));

		$prepared_args = array(
			'post_type' => 'leat_spend_rule',
			'posts_per_page' => -1,
			'post_status' => array('publish', 'draft', 'pending'),
		);

		$current_spend_rules = get_posts($prepared_args);
		$this->logger->info("Current spend rules in WordPress: " . count($current_spend_rules));

		// Collect existing Leat UUIDs from CPT
		$existing_uuids = array_column($current_spend_rules, '_leat_reward_uuid', 'ID');

		// Sync Leat rewards with CPT (add/update)
		$processed_uuids = [];
		$updated_count = 0;
		$created_count = 0;

		foreach ($rewards as $reward) {
			$mapped_reward = [
				'title' => $reward['title'],
				'requiredCredits' => $reward['requiredCredits'],
				'type' => 'ORDER_DISCOUNT',
				'uuid' => $reward['uuid'],
				'active' => $reward['active'],
				'selectedReward' => $reward['uuid'],
			];

			if(isset($reward['media'])) {
				$mapped_reward['image'] = $reward['media']['value'];
			}

			// Check if the reward already exists in CPT
			$existing_post_id = array_search($reward['uuid'], $existing_uuids);

			if ($existing_post_id !== false) {
				// Update existing spend rule
				$this->logger->info("Updating existing spend rule: " . $existing_post_id . " (UUID: " . $reward['uuid'] . ")");

				$this->spend_rules_service->create_or_update_spend_rule_from_reward($mapped_reward, $existing_post_id);
				$updated_count++;
			} else {
				// Create new spend rule
				$this->logger->info("Creating new spend rule for UUID: " . $reward['uuid']);

				$this->spend_rules_service->create_or_update_spend_rule_from_reward($mapped_reward);
				$created_count++;
			}

			$processed_uuids[] = $reward['uuid'];
		}

		// Delete spend rules that no longer exist in Leat
		$uuids_to_delete = array_diff($existing_uuids, $processed_uuids);
		$delete_count = count($uuids_to_delete);
		$this->logger->info("Deleting " . $delete_count . " spend rules that no longer exist in Leat");
		$this->spend_rules_service->delete_spend_rules_by_uuids($uuids_to_delete);

		// Handle duplicated UUIDs
		$this->logger->info("Handling any duplicated spend rules");
		$this->spend_rules_service->handle_duplicated_spend_rules($processed_uuids);

		$this->logger->info("Reward sync completed. Updated: $updated_count, Created: $created_count, Deleted: $delete_count");

		return true;
	}

	public function manual_sync_rewards() {
		return $this->sync_rewards_with_spend_rules();
	}

	public function create_reward_reception($contact_uuid, $reward_uuid) {
		$client = $this->init_client();
		if (!$client) return false;

		$shop_uuid = get_option('leat_shop_uuid', null);

		if (!$shop_uuid) {
			$this->logger->error("Shop UUID not set. Unable to create Reward Reception.");

			return;
		}

		$reception = RewardReception::create([
			"contact_uuid" => $contact_uuid,
			"reward_uuid" => $reward_uuid,
			"shop_uuid" => $shop_uuid
		]);

		return $reception ?: false;
	}

	public function refund_credits_full($credit_reception_uuid) {
		$client = $this->init_client();
		if (!$client) {
			return false;
		}

		try {
			$refund_result = CreditReception::reverse($credit_reception_uuid);

			if (!$refund_result) {
				$this->logger->error("Failed to process full refund for credit reception: $credit_reception_uuid");
				return false;
			}

			return $refund_result;
		} catch (\Exception $e) {
			$this->logger->error("Error processing full refund: " . $e->getMessage());
			return false;
		}
	}

	public function refund_credits_partial($credit_reception_uuid, $refund_percentage, $original_credits) {
		$client = $this->init_client();
		if (!$client) {
			return false;
		}

		try {
			$credits_to_refund = round($original_credits * $refund_percentage);

			$refund_result = CreditReception::create([
				'credits' => -$credits_to_refund,
			]);

			if (!$refund_result) {
				$this->logger->error("Failed to process partial refund for credit reception: $credit_reception_uuid");
				return false;
			}

			return $refund_result;
		} catch (\Exception $e) {
			$this->logger->error("Error processing partial refund: " . $e->getMessage());
			return false;
		}
	}
}