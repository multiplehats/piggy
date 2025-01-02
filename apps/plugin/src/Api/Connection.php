<?php

namespace Leat\Api;

use Error;
use Piggy\Api\RegisterClient;
use Piggy\Api\ApiClient;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use Piggy\Api\Models\Shops\Shop;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\Models\Loyalty\Receptions\RewardReception;
use Piggy\Api\Exceptions\PiggyRequestException;
use Leat\Domain\Services\SpendRules;
use Leat\Domain\Services\PromotionRules;
use Leat\Utils\Logger;
use Piggy\Api\Models\Giftcards\Giftcard;
use Piggy\Api\Models\Giftcards\GiftcardProgram;
use Piggy\Api\Models\Giftcards\GiftcardTransaction;
use Piggy\Api\Models\Vouchers\Promotion;

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
	 * PromotionRules service instance.
	 *
	 * @var PromotionRules
	 */
	protected $promotion_rules_service;

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

		if ( $api_key ) {
			$this->client = new RegisterClient( $api_key );
		} else {
			$this->client = null;
		}

		$this->spend_rules_service     = new SpendRules();
		$this->promotion_rules_service = new PromotionRules();
		$this->logger                  = new Logger();
	}

	/**
	 * Get the Leat API key.
	 *
	 * @return string|null The Leat API key.
	 */
	public function get_api_key() {
		$api_key = get_option( 'leat_api_key', null );

		if ( ! $api_key ) {
			$api_key = get_option( 'piggy_api_key', null );
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

		if ( $api_key ) {
			ApiClient::configure( $api_key, 'https://api.piggy.eu' );

			ApiClient::setPartnerId( 'P01-267-loyal_minds' );

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
		$subscriptions     = $contact->getSubscriptions();
		$subscription_list = [];

		if ( $subscriptions ) {
			foreach ( $subscriptions as $subscription ) {
				$type = $subscription->getSubscriptionType();

				$subscription_list[] = [
					'is_subscribed' => $subscription->isSubscribed(),
					'status'        => $subscription->getStatus(),
					'type'          => [
						'uuid'        => $type->getUuid(),
						'name'        => $type->getName(),
						'description' => $type->getDescription(),
						'active'      => $type->isActive(),
						'strategy'    => $type->getStrategy(),
					],
				];
			}
		}

		return [
			'uuid'          => $contact->getUuid(),
			'email'         => $contact->getEmail(),
			'subscriptions' => isset( $subscription_list ) ? $subscription_list : [],
			'attributes'    => $contact->getCurrentValues(),
			'balance'       => [
				'prepaid' => $contact->getPrepaidBalance()->getBalanceInCents(),
				'credits' => $contact->getCreditBalance()->getBalance(),
			],
		];
	}

	/**
	 * Get a contact.
	 *
	 * @param string $wp_user_id The WordPress user ID.
	 * @return array
	 */
	public function get_contact( string $wp_user_id ) {
		$client = $this->init_client();

		if ( ! $client ) {
			return null;
		}

		$wp_user = get_user_by( 'id', $wp_user_id );

		$contact = Contact::findOrCreate( [ 'email' => $wp_user->user_email ] );

		if ( ! $contact ) {
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

		if ( ! $client ) {
			return null;
		}

		try {
			$contact = Contact::findOrCreate( [ 'email' => $email ] );

			if ( ! $contact ) {
				throw new \Exception( 'Contact not found - returned null' );
			}

			return $this->format_contact( $contact );
		} catch ( \Throwable $th ) {
			$this->logException( $th, 'Contact Create Error' );

			throw $th;
		}
	}

	/**
	 * Update a contact.
	 *
	 * @param string $id The contact ID.
	 * @param array  $attributes The contact attributes.
	 *
	 * @return array|null
	 */
	public function update_contact( string $id, array $attributes ) {
		$client = $this->init_client();

		if ( ! $client ) {
			$this->logger->error( 'Failed to initialize client in update_contact' );
			return null;
		}

		try {
			$this->logger->info( 'Getting contact with ID: ' . $id );
			$contact = Contact::get( $id );

			if ( ! $contact ) {
				$this->logger->error( 'Contact not found with ID: ' . $id );
				return null;
			}

			$contact = Contact::update( $id, [ 'attributes' => $attributes ] );

			if ( ! $contact ) {
				throw new \Exception( 'Contact update failed - returned null' );
			}

			return $this->format_contact( $contact );
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Contact Update Error' );

			throw $e;
		}
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

		if ( ! $client ) {
			return null;
		}

		$results = Shop::list();

		if ( ! $results ) {
			return null;
		}

		$shops = [];

		foreach ( $results as $shop ) {
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

		if ( ! $client ) {
			return null;
		}

		$shop = Shop::get( $id );

		if ( ! $shop ) {
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
		$media     = $media_obj ? [
			'type'  => $media_obj->getType(),
			'value' => $media_obj->getValue(),
		] : null;

		return [
			'uuid'            => $reward->getUuid(),
			'title'           => $reward->getTitle(),
			'requiredCredits' => $reward->getRequiredCredits(),
			'type'            => $reward->getRewardType(),
			'active'          => $reward->isActive(),
			'attributes'      => $reward->getAttributes(),
			'media'           => $media,
		];
	}

	public function format_promotion( Promotion $promotion ) {
		return [
			'uuid'               => $promotion->getUuid(),
			'title'              => $promotion->getName(),
			'voucherLimit'       => $promotion->getVoucherLimit(),
			'limitPerContact'    => $promotion->getLimitPerContact(),
			'expirationDuration' => $promotion->getExpirationDuration(),
			'attributes'         => $promotion->getAttributes(),
		];
	}

	/**
	 * Get the rewards.
	 *
	 * @return array|null
	 */
	public function get_rewards() {
		$client = $this->init_client();

		if ( ! $client ) {
			return null;
		}

		$results = Reward::list();

		if ( ! $results ) {
			return null;
		}

		$rewards = [];

		foreach ( $results as $reward ) {
			$rewards[] = $this->format_reward( $reward );
		}

		return $rewards;
	}

	public function get_promotions() {
		$client = $this->init_client();

		if ( ! $client ) {
			return null;
		}

		$results = Promotion::list();

		$promotions = array();

		foreach ( $results as $promotion ) {
			$promotions[] = $this->format_promotion( $promotion );
		}

		return $promotions;
	}

	public function apply_credits( string $contact_uuid, ?int $credits = null, ?float $unit_value = null, ?string $unit_name = null ) {
		$client = $this->init_client();
		if ( ! $client ) {
			return false;
		}

		$shop_uuid = get_option( 'leat_shop_uuid', null );
		if ( ! $shop_uuid ) {
			return false;
		}

		$params = [
			'shop_uuid'    => $shop_uuid,
			'contact_uuid' => $contact_uuid,
		];

		if ( $credits !== null ) {
			$params['credits'] = $credits;
		}

		if ( $unit_value !== null ) {
			$params['unit_value'] = $unit_value;
		}

		if ( $unit_name !== null ) {
			$params['unit_name'] = $unit_name;
		}

		// Ensure that either credits or unit_value is set
		if ( ! isset( $params['credits'] ) && ! isset( $params['unit_value'] ) ) {
			return false;
		}

		$reception = CreditReception::create( $params );

		return $reception ?: false;
	}

	/**
	 * Get the contact UUID by WordPress user ID.
	 *
	 * @param int $wp_id
	 * @return string|null
	 */
	public function get_contact_uuid_by_wp_id( $wp_id, $create = false ) {
		$uuid = get_user_meta( $wp_id, 'leat_uuid', true );

		if ( ! $uuid && $create ) {
			$contact = $this->create_contact( get_the_author_meta( 'email', $wp_id ) );
			$uuid    = $contact['uuid'];

			$this->sync_user_attributes( $wp_id, $uuid );

			return $uuid;
		}

		return $uuid;
	}

	public function get_user_from_leat_uuid( $uuid ) {
		return get_user_by( 'meta_key', 'leat_uuid', $uuid );
	}

	/**
	 * Get WooCommerce user data.
	 *
	 * @param int $user_id
	 * @return array
	 */
	private function get_woocommerce_user_data( $user_id ) {
		if ( ! function_exists( 'wc_get_customer_total_spent' ) || ! function_exists( 'wc_get_customer_order_count' ) ) {
			return $this->get_default_wc_attributes();
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return $this->get_default_wc_attributes();
		}

		$total_spent  = wc_get_customer_total_spent( $user_id );
		$orders_count = wc_get_customer_order_count( $user_id );
		$create_date  = $user->user_registered;

		$customer_orders = wc_get_orders(
			[
				'customer' => $user_id,
				'limit'    => 1,
				'orderby'  => 'date',
				'order'    => 'DESC',
			]
			);

		$last_order_amount = 0;
		$last_order_date   = '';

		if ( ! empty( $customer_orders ) ) {
			$last_order        = $customer_orders[0];
			$last_order_amount = $last_order->get_total();
			$last_order_date   = $last_order->get_date_created()->format( 'Y-m-d H:i:s' );
		}

		$currency         = strtolower( get_woocommerce_currency() );
		$first_order_date = $this->get_first_order_date( $user_id );

		return [
			'wp_wc_total_spent_' . $currency         => (float) $total_spent,
			'wp_wc_orders_count'                     => (int) $orders_count,
			'wp_create_date'                         => $create_date,
			'wp_wc_last_order_amount_' . $currency   => (float) $last_order_amount,
			'wp_wc_last_order_date'                  => $last_order_date,
			'wp_wc_average_order_value_' . $currency => $orders_count > 0 ? round( $total_spent / $orders_count, 2 ) : 0,
			'wp_wc_first_order_date'                 => $first_order_date,
			'wp_wc_product_categories_purchased'     => $this->get_purchased_categories( $user_id ),
			'wp_wc_total_products_purchased'         => $this->get_total_products_purchased( $user_id ),
		];
	}

	private function get_default_wc_attributes() {
		$currency = strtolower( get_woocommerce_currency() );
		return [
			'wp_wc_total_spent_' . $currency         => 0,
			'wp_wc_orders_count'                     => 0,
			'wp_create_date'                         => '',
			'wp_wc_last_order_amount_' . $currency   => 0,
			'wp_wc_last_order_date'                  => '',
			'wp_wc_average_order_value_' . $currency => 0,
			'wp_wc_first_order_date'                 => '',
			'wp_wc_product_categories_purchased'     => [],
			'wp_wc_total_products_purchased'         => 0,
		];
	}

	private function get_first_order_date( $user_id ) {
		$customer_orders = wc_get_orders(
			[
				'customer' => $user_id,
				'limit'    => 1,
				'orderby'  => 'date',
				'order'    => 'ASC',
			]
			);

		if ( ! empty( $customer_orders ) ) {
			$first_order = $customer_orders[0];
			return $first_order->get_date_created()->format( 'Y-m-d H:i:s' );
		}

		return '';
	}

	private function get_purchased_categories( $user_id, $current_order = null ) {
		$categories = [];

		// Handle current order if provided (for guests)
		if ( $current_order ) {
			foreach ( $current_order->get_items() as $item ) {
				/**
				 * Process each product
				 *
				 * @var \WC_Order_Item_Product $item
				 */
				$product = $item->get_product();

				if ( $product ) {
					$categories = array_merge( $categories, $product->get_category_ids() );
				}
			}

			// Get previous orders excluding current order
			$customer_orders = wc_get_orders(
				[
					'customer' => $current_order->get_billing_email(),
					'status'   => [ 'completed', 'processing', 'on-hold' ],
					'limit'    => -1,
					'exclude'  => [ $current_order->get_id() ],
				]
				);
		} else {
			// Get all orders for registered user
			$customer_orders = wc_get_orders( [ 'customer' => $user_id ] );
		}

		// Process historical orders
		foreach ( $customer_orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				/**
				 * Process each product
				 *
				 * @var \WC_Order_Item_Product $item
				 */
				$product = $item->get_product();
				if ( $product ) {
					$categories = array_merge( $categories, $product->get_category_ids() );
				}
			}
		}

		// Convert to strings and ensure unique values
		return array_map( 'strval', array_unique( $categories ) );
	}

	private function get_total_products_purchased( $user_id ) {
		$total_products  = 0;
		$customer_orders = wc_get_orders( [ 'customer' => $user_id ] );

		foreach ( $customer_orders as $order ) {
			foreach ( $order->get_items() as $item ) {
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
	protected function get_user_attributes( $user_id ) {
		$user       = get_userdata( $user_id );
		$attributes = [
			'wp_user_id'          => $user_id,
			'firstname'           => $user->first_name,
			'lastname'            => $user->last_name,
			'wp_user_role'        => implode( ', ', $user->roles ),
			'wp_account_age_days' => floor( ( time() - strtotime( $user->user_registered ) ) / ( 60 * 60 * 24 ) ),
			'wp_last_login'       => get_user_meta( $user_id, 'leat_last_login', true ) ?: '',
			'wp_post_count'       => count_user_posts( $user_id ),
		];

		$wc_attributes = $this->get_woocommerce_user_data( $user_id );

		$attributes = array_merge( $attributes, $wc_attributes );

		$this->ensure_custom_attributes_exist();

		return $attributes;
	}

	private function attribute_exists( $attributes_list, $name ) {
		foreach ( $attributes_list as $attribute ) {
			if ( $attribute->getName() === $name ) {
				return true;
			}
		}
		return false;
	}

	private function get_product_categories_options() {
		$categories = get_terms(
		 [
			 'taxonomy'   => 'product_cat',
			 'hide_empty' => false,
		 ]
		);
		$options    = [];

		foreach ( $categories as $category ) {
			$options[] = [
				'label' => $category->name,
				'value' => $category->term_id,
			];
		}

		return $options;
	}

	/**
	 * Ensure custom attributes exist in Leat.
	 */
	private function ensure_custom_attributes_exist() {
		 $client = $this->init_client();

		if ( ! $client ) {
			$this->logger->error( 'Failed to initialize client' );
			return;
		}

		try {
			$attributes_list = CustomAttribute::list( [ 'entity' => 'contact' ] );
			$currency        = strtolower( get_woocommerce_currency() );

			// Define required attributes with proper format
			$required_attributes = [
				[
					'entity' => 'contact',
					'name'   => 'wp_user_id',
					'label'  => 'WordPress User ID',
					'type'   => 'number',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_user_role',
					'label'  => 'WordPress User Role',
					'type'   => 'text',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_account_age_days',
					'label'  => 'WordPress Account Age (Days)',
					'type'   => 'number',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_last_login',
					'label'  => 'WordPress Last Login',
					'type'   => 'date_time',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_post_count',
					'label'  => 'WordPress Post Count',
					'type'   => 'number',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_total_spent_' . $currency,
					'label'  => 'WooCommerce Total Spent (' . strtoupper( $currency ) . ')',
					'type'   => 'float',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_orders_count',
					'label'  => 'WooCommerce Orders Count',
					'type'   => 'number',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_create_date',
					'label'  => 'WordPress Create Date',
					'type'   => 'date_time',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_last_order_amount_' . $currency,
					'label'  => 'WooCommerce Last Order Amount (' . strtoupper( $currency ) . ')',
					'type'   => 'float',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_last_order_date',
					'label'  => 'WooCommerce Last Order Date',
					'type'   => 'date_time',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_average_order_value_' . $currency,
					'label'  => 'WooCommerce Average Order Value (' . strtoupper( $currency ) . ')',
					'type'   => 'float',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_first_order_date',
					'label'  => 'WooCommerce First Order Date',
					'type'   => 'date_time',
				],
				[
					'entity'  => 'contact',
					'name'    => 'wp_wc_product_categories_purchased',
					'label'   => 'WooCommerce Product Categories Purchased',
					'type'    => 'multi_select',
					'options' => array_map(
						function ( $option ) {
							return [
								'value' => (string) $option['value'],
								'label' => $option['label'],
							];
						},
						$this->get_product_categories_options()
						),
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_total_products_purchased',
					'label'  => 'WooCommerce Total Products Purchased',
					'type'   => 'number',
				],
			];

			foreach ( $required_attributes as $attr ) {
				if ( ! $this->attribute_exists( $attributes_list, $attr['name'] ) ) {
					try {
						// Create single attribute at a time
						$response = ApiClient::post( CustomAttribute::resourceUri, $attr );
					} catch ( \Exception $e ) {
						$this->logException( $e, 'Attribute "' . $attr['name'] . '" Create Error' );
					}
				}
			}
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Ensure Custom Attributes Error' );
		}
	}

	/**
	 * Sync user/guest attributes with Leat, ensuring categories are up to date
	 */
	private function sync_attributes_with_category_update( $uuid, $attributes ) {
		try {
			$this->ensure_custom_attributes_exist();

			$update_result = $this->update_contact( $uuid, $attributes );

			if ( $update_result === null ) {
				$this->logger->error( 'Update contact returned null' );
				return false;
			}

			return $update_result;
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Sync Attributes with Category Update Error' );

			return false;
		}
	}

	/**
	 * Update sync_user_attributes to use the new method
	 */
	public function sync_user_attributes( $user_id, $uuid ) {
		try {
			$user = get_userdata( $user_id );

			if ( ! $user ) {
				throw new \Exception( 'User not found' );
			}

			$attributes = $this->get_user_attributes( $user_id );
			return $this->sync_attributes_with_category_update( $uuid, $attributes );
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Sync User Attributes Error' );

			return false;
		}
	}

	public function sync_basic_attributes_from_order( $order, $uuid, $is_guest ) {
		try {
			if ( $is_guest ) {
				$attributes = [
					'firstname'    => $order->get_billing_first_name(),
					'lastname'     => $order->get_billing_last_name(),
					'wp_user_role' => 'guest',
				];
			} else {
				$user_id = $order->get_user_id();
				$user    = get_userdata( $user_id );

				$attributes = [
					'wp_user_id'          => $user_id,
					'firstname'           => $user->first_name,
					'lastname'            => $user->last_name,
					'wp_user_role'        => implode( ', ', $user->roles ),
					'wp_account_age_days' => floor( ( time() - strtotime( $user->user_registered ) ) / ( 60 * 60 * 24 ) ),
					'wp_last_login'       => get_user_meta( $user_id, 'leat_last_login', true ) ?: '',
					'wp_post_count'       => count_user_posts( $user_id ),
				];
			}

			return $this->sync_attributes_with_category_update( $uuid, $attributes );
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Sync Basic Attributes from Order Error' );
			return false;
		}
	}

	/**
	 * Update sync_guest_attributes to use the new method
	 */
	public function sync_guest_attributes( $order, $uuid ) {
		try {
			$email = $order->get_billing_email();
			if ( ! $email ) {
				throw new \Exception( 'No email provided for guest order' );
			}

			$attributes = $this->get_woocommerce_guest_data( $email, $order );
			return $this->sync_attributes_with_category_update( $uuid, $attributes );
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Sync Guest Attributes Error' );
			return false;
		}
	}

	/**
	 * Get all user metadata regarding leat_
	 *
	 * @param int $wp_user_id
	 * @return array
	 */
	public function get_user_leat_metadata( $wp_user_id ) {
		$meta_data = get_user_meta( $wp_user_id );

		$leat_meta_data = array_filter(
			$meta_data,
			function ( $key ) {
				return strpos( $key, 'leat_' ) === 0;
			},
			ARRAY_FILTER_USE_KEY,
		);

		return $leat_meta_data;
	}

	/**
	 * Get user reward logs
	 *
	 * @param int $wp_user_id
	 * @return array
	 */
	public function get_user_reward_logs( $wp_user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'leat_reward_logs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leat_reward_logs WHERE wp_user_id = %d", $wp_user_id );

		$cache_key   = 'leat_reward_logs_' . $wp_user_id;
		$reward_logs = wp_cache_get( $cache_key );

		if ( false === $reward_logs ) {
			$reward_logs = $wpdb->get_results( $query, ARRAY_A );
			wp_cache_set( $cache_key, $reward_logs, '', 3600 );
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
	public function add_reward_log( $wp_user_id, $earn_rule_id, $credits ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'leat_reward_logs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$data = [
			'wp_user_id'   => $wp_user_id,
			'earn_rule_id' => $earn_rule_id,
			'credits'      => $credits,
			'timestamp'    => current_time( 'mysql', 1 ),
		];

		$format = [
			'%d', // wp_user_id
			'%d', // earn_rule_id
			'%d', // credits
			'%s', // timestamp
		];

		$inserted = $wpdb->insert( $table_name, $data, $format );
		// phpcs:enable

		// Clear cache after inserting new log
		wp_cache_delete( 'leat_reward_logs_' . $wp_user_id );

		return $inserted !== false;
	}

	/**
	 * Syncs rewards in Leat with the spend rules CPT.
	 *
	 * @return bool
	 */
	public function sync_rewards_with_spend_rules() {
		$client = $this->init_client();
		if ( ! $client ) {
			$this->logger->error( 'Failed to initialize client for reward sync' );
			return false;
		}

		$rewards = $this->get_rewards();
		if ( ! $rewards ) {
			$this->logger->error( 'Failed to retrieve rewards from Leat' );
			return false;
		}

		$this->logger->info( 'Starting reward sync. Total rewards retrieved: ' . count( $rewards ) );

		$prepared_args = [
			'post_type'      => 'leat_spend_rule',
			'posts_per_page' => -1,
			'post_status'    => [ 'publish', 'draft', 'pending' ],
		];

		$current_spend_rules = get_posts( $prepared_args );
		$this->logger->info( 'Current spend rules in WordPress: ' . count( $current_spend_rules ) );

		// Collect existing Leat UUIDs from CPT
		$existing_uuids = array_column( $current_spend_rules, '_leat_reward_uuid', 'ID' );

		// Sync Leat rewards with CPT (add/update)
		$processed_uuids = [];
		$updated_count   = 0;
		$created_count   = 0;

		foreach ( $rewards as $reward ) {
			$mapped_reward = [
				'title'           => $reward['title'],
				'requiredCredits' => $reward['requiredCredits'],
				'type'            => 'ORDER_DISCOUNT',
				'uuid'            => $reward['uuid'],
				'active'          => $reward['active'],
				'selectedReward'  => $reward['uuid'],
			];

			if ( isset( $reward['media'] ) ) {
				$mapped_reward['image'] = $reward['media']['value'];
			}

			// Check if the reward already exists in CPT
			$existing_post_id = array_search( $reward['uuid'], $existing_uuids );

			if ( $existing_post_id !== false ) {
				// Update existing spend rule
				$this->logger->info( 'Updating existing spend rule: ' . $existing_post_id . ' (UUID: ' . $reward['uuid'] . ')' );

				$this->spend_rules_service->create_or_update_spend_rule_from_reward( $mapped_reward, $existing_post_id );
				$updated_count++;
			} else {
				// Create new spend rule
				$this->logger->info( 'Creating new spend rule for UUID: ' . $reward['uuid'] );

				$this->spend_rules_service->create_or_update_spend_rule_from_reward( $mapped_reward );
				$created_count++;
			}

			$processed_uuids[] = $reward['uuid'];
		}

		// Delete spend rules that no longer exist in Leat
		$uuids_to_delete = array_diff( $existing_uuids, $processed_uuids );
		$delete_count    = count( $uuids_to_delete );
		$this->logger->info( 'Deleting ' . $delete_count . ' spend rules that no longer exist in Leat' );
		$this->spend_rules_service->delete_spend_rules_by_uuids( $uuids_to_delete );

		// Handle duplicated UUIDs
		$this->logger->info( 'Handling any duplicated spend rules' );
		$this->spend_rules_service->handle_duplicated_spend_rules( $processed_uuids );

		$this->logger->info( "Reward sync completed. Updated: $updated_count, Created: $created_count, Deleted: $delete_count" );

		return true;
	}

	/**
	 * Syncs promotions/vouchers in Leat with the promotion rules CPT.
	 *
	 * @return bool
	 */
	public function sync_promotions_with_promotion_rules() {
		$client = $this->init_client();
		if ( ! $client ) {
			$this->logger->error( 'Failed to initialize client for reward sync' );
			return false;
		}

		$promotions = $this->get_promotions();

		if ( ! $promotions ) {
			return true;
		}

		$this->logger->info( 'Starting promotion sync. Total promotions retrieved: ' . count( $promotions ) );

		$prepared_args = array(
			'post_type'      => 'leat_promotion_rule',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'pending' ),
		);

		$current_promotion_rules = get_posts( $prepared_args );

		$this->logger->info( 'Current promotion rules in WordPress: ' . count( $current_promotion_rules ) );

		// Collect existing Leat UUIDs from CPT
		$existing_uuids = array_column( $current_promotion_rules, '_leat_promotion_uuid', 'ID' );

		// Sync Leat rewards with CPT (add/update)
		$processed_uuids = [];
		$updated_count   = 0;
		$created_count   = 0;

		foreach ( $promotions as $promotion ) {
			$mapped_promotion = [
				'title'              => $promotion['title'],
				'uuid'               => $promotion['uuid'],
				'voucherLimit'       => $promotion['voucherLimit'],
				'limitPerContact'    => $promotion['limitPerContact'],
				'expirationDuration' => $promotion['expirationDuration'],
			];

			if ( isset( $promotion['media'] ) ) {
				$mapped_promotion['image'] = $promotion['media']['value'];
			}

			// Check if the promotion already exists in CPT
			$existing_post_id = array_search( $promotion['uuid'], $existing_uuids );

			if ( $existing_post_id !== false ) {
				// Update existing promotion rule
				$this->logger->info( 'Updating existing promotion rule: ' . $existing_post_id . ' (UUID: ' . $promotion['uuid'] . ')' );

				$this->promotion_rules_service->create_or_update_promotion_rule_from_promotion( $mapped_promotion, $existing_post_id );
				$updated_count++;
			} else {
				// Create new spend rule
				$this->logger->info( 'Creating new promotion rule for UUID: ' . $promotion['uuid'] );

				$this->promotion_rules_service->create_or_update_promotion_rule_from_promotion( $mapped_promotion );
				$created_count++;
			}

			$processed_uuids[] = $promotion['uuid'];
		}

		// Delete promotion rules that no longer exist in Leat
		$uuids_to_delete = array_diff( $existing_uuids, $processed_uuids );
		$delete_count    = count( $uuids_to_delete );
		$this->logger->info( 'Deleting ' . $delete_count . ' promotion rules that no longer exist in Leat' );
		$this->promotion_rules_service->delete_promotion_rules_by_uuids( $uuids_to_delete );

		// Handle duplicated UUIDs
		$this->logger->info( 'Handling any duplicated promotion rules' );
		$this->promotion_rules_service->handle_duplicated_promotion_rules( $processed_uuids );

		$this->logger->info( "Promotion sync completed. Updated: $updated_count, Created: $created_count, Deleted: $delete_count" );

		do_action( 'leat_sync_promotions_complete', $processed_uuids );

		return true;
	}

	public function create_reward_reception( $contact_uuid, $reward_uuid ) {
		$client = $this->init_client();
		if ( ! $client ) {
			return false;
		}

		$shop_uuid = get_option( 'leat_shop_uuid', null );

		if ( ! $shop_uuid ) {
			$this->logger->error( 'Shop UUID not set. Unable to create Reward Reception.' );

			return;
		}

		$reception = RewardReception::create(
			[
				'contact_uuid' => $contact_uuid,
				'reward_uuid'  => $reward_uuid,
				'shop_uuid'    => $shop_uuid,
			]
			);

		return $reception ?: false;
	}

	public function refund_credits_full( $credit_reception_uuid ) {
		$client = $this->init_client();
		if ( ! $client ) {
			return false;
		}

		try {
			$refund_result = CreditReception::reverse( $credit_reception_uuid );

			if ( ! $refund_result ) {
				$this->logger->error( "Failed to process full refund for credit reception: $credit_reception_uuid" );
				return false;
			}

			return $refund_result;
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Error Processing Full Refund' );
			return false;
		}
	}

	public function refund_credits_partial( $credit_reception_uuid, $refund_percentage, $original_credits ) {
		$client = $this->init_client();
		if ( ! $client ) {
			return false;
		}

		try {
			$credits_to_refund = round( $original_credits * $refund_percentage );

			$refund_result = CreditReception::create(
				[
					'credits' => -$credits_to_refund,
				]
				);

			if ( ! $refund_result ) {
				$this->logger->error( "Failed to process partial refund for credit reception: $credit_reception_uuid" );
				return false;
			}

			return $refund_result;
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Error Processing Partial Refund' );
			return false;
		}
	}

	/**
	 * Get WooCommerce guest data from order history
	 *
	 * @param string   $email Customer email
	 * @param WC_Order $current_order Current order being processed
	 * @return array
	 */
	private function get_woocommerce_guest_data( $email, $current_order ) {
		$currency = strtolower( get_woocommerce_currency() );

		// Get guest's order history - exclude current order
		$customer_orders = wc_get_orders(
			[
				'customer' => $email,
				'status'   => [ 'completed', 'processing', 'on-hold' ],
				'limit'    => -1,
				'exclude'  => [ $current_order->get_id() ], // Explicitly exclude current order
			]
			);

		$this->logger->info( 'Found ' . count( $customer_orders ) . ' previous orders for guest email: ' . $email );

		// Initialize statistics
		$total_spent    = $current_order->get_total(); // Start with current order
		$total_products = 0;
		$orders_count   = 1; // Start with 1 for current order

		// Set initial dates from current order
		$current_order_date = $current_order->get_date_created()->format( 'Y-m-d H:i:s' );
		$first_order_date   = $current_order_date;
		$last_order_date    = $current_order_date;
		$last_order_amount  = $current_order->get_total();

		$average_order_value = $orders_count > 0 ? $total_spent / $orders_count : 0;

		return [
			'wp_wc_total_spent_' . $currency         => (float) $total_spent,
			'wp_wc_orders_count'                     => (int) $orders_count,
			'wp_wc_last_order_amount_' . $currency   => (float) $last_order_amount,
			'wp_wc_last_order_date'                  => $last_order_date,
			'wp_wc_average_order_value_' . $currency => (float) $average_order_value,
			'wp_wc_first_order_date'                 => $first_order_date,
			'wp_wc_product_categories_purchased'     => $this->get_purchased_categories( null, $current_order ),
			'wp_wc_total_products_purchased'         => (int) $total_products,
			'wp_user_id'                             => $current_order->get_user_id(),
			'firstname'                              => $current_order->get_billing_first_name(),
			'lastname'                               => $current_order->get_billing_last_name(),
			'wp_user_role'                           => 'guest',
		];
	}

	/**
	 * List all gift card programs
	 *
	 * @return array|null
	 */
	public function list_giftcard_programs() {
		$client = $this->init_client();
		if ( ! $client ) {
			return null;
		}

		try {
			$response = GiftcardProgram::list();

			$programs = [];

			foreach ( $response as $program ) {
				$programs[] = $this->format_giftcard_program( $program );
			}

			return $programs;
		} catch ( \Exception $e ) {
			$this->logException( $e, 'List Giftcard Programs Error' );

			throw $e;
		}
	}

	private function format_giftcard_program( GiftcardProgram $program ): array {
		return [
			'uuid'                => $program->getUuid(),
			'name'                => $program->getName(),
			'active'              => $program->isActive(),
			'max_amount_in_cents' => $program->getMaxAmountInCents(),
			'calculator_flow'     => $program->getCalculatorFlow(),
			'expiration_days'     => $program->getExpirationDays(),
		];
	}

	/**
	 * Log API errors.
	 *
	 * @param \Exception $e The exception to log
	 * @param string     $context Additional context for the error
	 */
	private function logException( \Exception $e, string $context = '' ) {
		if ( $e instanceof PiggyRequestException ) {
			$error_bag = $e->getErrorBag();
			$this->logger->error(
				'API Error Details: ' .
				wp_json_encode(
					[
						'message'     => $e->getMessage(),
						'code'        => $e->getCode(),
						'error_bag'   => $error_bag ? json_encode( $error_bag->all() ) : null,
						'first_error' => $error_bag ? json_encode( $error_bag->first() ) : null,
						'context'     => $context,
					],
					JSON_PRETTY_PRINT,
				),
			);
		} else {
			$this->logger->error(
				$context . ': ' .
				wp_json_encode(
					[
						'error' => $e->getMessage(),
						'trace' => $e->getTraceAsString(),
					]
					),
			);
		}
	}

	public function create_giftcard( $giftcard_program_uuid ) {
		$client = $this->init_client();
		if ( ! $client ) {
			return null;
		}

		try {
			$response = Giftcard::create(
				[
					'type'                  => 1,
					'giftcard_program_uuid' => $giftcard_program_uuid,
				]
				);

			return $this->format_giftcard( $response );
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Create Giftcard Error' );
			throw $e;
		}
	}

	public function create_giftcard_transaction( $giftcard_uuid, $amount_in_cents ) {
		$client = $this->init_client();
		if ( ! $client ) {
			return null;
		}

		$shop_uuid = get_option( 'leat_shop_uuid', null );
		if ( ! $shop_uuid ) {
			return false;
		}

		$response = GiftcardTransaction::create(
			[
				'shop_uuid'       => $shop_uuid,
				'giftcard_uuid'   => $giftcard_uuid,
				'amount_in_cents' => $amount_in_cents,
				'type'            => 1,
			]
			);

		return $this->format_giftcard_transaction( $response );
	}

	private function format_giftcard( Giftcard $giftcard ) {
		return [
			'id'   => $giftcard->getId(),
			'uuid' => $giftcard->getUuid(),
			'hash' => $giftcard->getHash(),
		];
	}

	private function format_giftcard_transaction( GiftcardTransaction $giftcard_transaction ) {
		return [
			'id'              => $giftcard_transaction->getId(),
			'uuid'            => $giftcard_transaction->getUuid(),
			'amount_in_cents' => $giftcard_transaction->getAmountInCents(),
		];
	}

	public function send_giftcard_email( $giftcard_uuid, $contact_uuid, $email_uuid = null, $merge_tags = [] ) {
		$client = $this->init_client();

		if ( ! $client ) {
			return false;
		}

		try {
			$payload = [
				'contact_uuid' => $contact_uuid,
			];

			if ( $email_uuid ) {
				$payload['email_uuid'] = $email_uuid;
			}

			if ( ! empty( $merge_tags ) ) {
				// Ensure merge tags are prefixed with 'custom.'.
				$formatted_tags = [];

				foreach ( $merge_tags as $key => $value ) {
					$key                    = strpos( $key, 'custom.' ) === 0 ? $key : 'custom.' . $key;
					$formatted_tags[ $key ] = $value;
				}
				$payload['merge_tags'] = $formatted_tags;
			}

			// TODO: this endpoint is not in the SDK yet.
			$response = ApiClient::post( "/api/v3/oauth/clients/giftcards/{$giftcard_uuid}/send-by-email", $payload );

			$this->logger->info(
				'Giftcard email sent successfully',
				[
					'giftcard_uuid' => $giftcard_uuid,
					'contact_uuid'  => $contact_uuid,
					'response'      => $response,
				]
				);

			return $response;
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Send Giftcard Email Error' );
			return false;
		}
	}

	/**
	 * Reverse a giftcard transaction (full refund)
	 *
	 * @param string $transaction_uuid UUID of the transaction to reverse.
	 * @return array|false The reversed transaction data or false on failure.
	 */
	public function reverse_giftcard_transaction( $transaction_uuid ) {
		$client = $this->init_client();
		if ( ! $client ) {
			return false;
		}

		try {
			$response = GiftcardTransaction::reverse( $transaction_uuid );

			return $this->format_giftcard_transaction( $response );
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Reverse Giftcard Transaction Error' );
			return false;
		}
	}

	/**
	 * Create a partial refund transaction for a giftcard
	 *
	 * @param string $giftcard_uuid UUID of the giftcard.
	 * @param int    $amount_in_cents Amount to refund in cents (should be negative).
	 * @return array|false The transaction data or false on failure
	 */
	public function create_giftcard_refund_transaction( $giftcard_uuid, $amount_in_cents ) {
		if ( $amount_in_cents >= 0 ) {
			// Ensure amount is negative for refunds.
			$amount_in_cents = -$amount_in_cents;
		}

		try {
			return $this->create_giftcard_transaction( $giftcard_uuid, $amount_in_cents );
		} catch ( \Exception $e ) {
			$this->logException( $e, 'Create Giftcard Refund Transaction Error' );
			return false;
		}
	}
}
