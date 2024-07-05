<?php
namespace PiggyWP\Api;

use Piggy\Api\RegisterClient;
use Piggy\Api\ApiClient;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\Shops\Shop;

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
				'credit'  => $contact->getCreditBalance()->getBalance(),
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

		// This throws an error, but we need to check if the attribute exists first, then create it.
		// THis probably should be moved to the onboarding or api key setup process.

		// $attributes_list = CustomAttribute::list(["entity" => "contact"]);
		// $result = CustomAttribute::create(["entity" => "contact", "name" => "wp_user_id", "label" => "WordPress User ID", "type" => "text" ]);
		$contact = Contact::update( $id, [ "attributes" => $attributes ] );

		if( ! $contact ) {
			return null;
		}

		return $this->format_contact( $contact );
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
}
