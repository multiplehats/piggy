<?php
namespace PiggyWP\Api;

use Piggy\Api\RegisterClient;
use Piggy\Api\ApiClient;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use Piggy\Api\Models\Shops\Shop;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\Models\Loyalty\Receptions\RewardReception;
use PiggyWP\Domain\Services\SpendRules;
use PiggyWP\Utils\Logger;

class Connection {
	/**
	 * Piggy Register Client instance.
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
	 * Get the Piggy API key.
	 *
	 * @return string|null The Piggy API key.
	 */
	public function get_api_key() {
		$api_key = get_option('piggy_api_key', null);

		return $api_key;
	}

	public function has_api_key() {
		$api_key = $this->get_api_key();

		return $api_key !== null && $api_key !== '';
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

	public function apply_credits(string $contact_uuid, ?int $credits = null, ?float $unit_value = null, ?string $unit_name = null) {
		$client = $this->init_client();
		if (!$client) return false;

		$shop_uuid = get_option('piggy_shop_uuid', null);
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

	public function sync_rewards_with_spend_rules() {
		$client = $this->init_client();
		if (!$client) {
			return false;
		}

		$rewards = $this->get_rewards();
		if (!$rewards) {
			return false;
		}

		// Fetch all current spend rules from CPT
		$current_spend_rules = $this->spend_rules_service->get_all_spend_rules();

		// Collect existing Piggy UUIDs from CPT
		$existing_uuids = array_column($current_spend_rules, '_piggy_reward_uuid', 'ID');

		// Sync Piggy rewards with CPT (add/update)
		$processed_uuids = [];
		foreach ($rewards as $reward) {
			$mapped_reward = [
				'title' => $reward['title'],
				'requiredCredits' => $reward['requiredCredits'],
				'type' => 'ORDER_DISCOUNT',
				'uuid' => $reward['uuid'],
				'active' => false,
				'selectedReward' => $reward['uuid'],
			];
			$this->spend_rules_service->create_or_update_spend_rule_from_reward($mapped_reward);
			$processed_uuids[] = $reward['uuid'];
		}

		// Delete spend rules that no longer exist in Piggy
		$uuids_to_delete = array_diff($existing_uuids, $processed_uuids);
		$this->spend_rules_service->delete_spend_rules_by_uuids($uuids_to_delete);
		$this->spend_rules_service->delete_spend_rules_with_empty_uuid();

		// Handle duplicated UUIDs
		$this->spend_rules_service->handle_duplicated_spend_rules($processed_uuids);

		return true;
	}

	public function manual_sync_rewards() {
		return $this->sync_rewards_with_spend_rules();
	}

	public function create_reward_reception($contact_uuid, $reward_uuid) {
		$client = $this->init_client();
		if (!$client) return false;

		$shop_uuid = get_option('piggy_shop_uuid', null);

		if (!$shop_uuid) {
			error_log("Shop UUID not set. Unable to create Reward Reception.");
			return;
		}

		$reception = RewardReception::create([
			"contact_uuid" => $contact_uuid,
			"reward_uuid" => $reward_uuid,
			"shop_uuid" => $shop_uuid
		]);

		return $reception ?: false;
	}
}
