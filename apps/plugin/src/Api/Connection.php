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
use Piggy\Api\Exceptions\PiggyRequestException;
use Leat\Utils\Logger;
use Leat\Utils\Users;
use Piggy\Api\Models\Giftcards\Giftcard;
use Piggy\Api\Models\Giftcards\GiftcardProgram;
use Piggy\Api\Models\Giftcards\GiftcardTransaction;
use Piggy\Api\Models\Vouchers\Promotion;
use Piggy\Api\Models\Vouchers\Voucher;

class Connection
{

	/**
	 * Register Client instance.
	 *
	 * @var RegisterClient
	 */
	protected $client;


	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$api_key = $this->get_api_key();

		if ($api_key) {
			$this->client = new RegisterClient($api_key);
		} else {
			$this->client = null;
		}

		$this->logger = new Logger();
	}


	/**
	 * Log API errors.
	 *
	 * @param \Exception $e The exception to log.
	 * @param string     $context Additional context for the error.
	 */
	private function log_exception(\Exception $e, string $context = '')
	{
		if ($e instanceof PiggyRequestException) {
			$error_bag = $e->getErrorBag();
			$this->logger->error(
				'API Error Details: ' .
					wp_json_encode(
						[
							'message'     => $e->getMessage(),
							'code'        => $e->getCode(),
							'error_bag'   => $error_bag ? wp_json_encode($error_bag->all()) : null,
							'first_error' => $error_bag ? wp_json_encode($error_bag->first()) : null,
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
							'trace' => defined('WP_DEBUG') && WP_DEBUG ? $e->getTraceAsString() : null,
						]
					),
			);
		}
	}

	/**
	 * Get the Leat API key.
	 *
	 * @return string|null The Leat API key.
	 */
	public function get_api_key()
	{
		$api_key = get_option('leat_api_key', null);

		if (! $api_key) {
			$api_key = get_option('piggy_api_key', null);
		}

		return $api_key;
	}

	public function has_api_key()
	{
		$api_key = $this->get_api_key();

		return null !== $api_key && '' !== $api_key;
	}

	/**
	 * Get the  Register Client instance.
	 *
	 * @return null|true
	 */
	public function init_client()
	{
		$api_key = $this->get_api_key();

		if ($api_key && strlen($api_key) === 53) {
			ApiClient::configure($api_key, 'https://api.piggy.eu');
			ApiClient::setPartnerId('P01-267-loyal_minds');

			$this->client = true;
			return $this->client;
		} else {
			$this->client = null;
			return $this->client;
		}
	}

	/**
	 * Get the Contacts response.
	 *
	 * @param Contact $contact The contact object.
	 *
	 * @return array
	 */
	private function format_contact(Contact $contact)
	{
		$subscriptions     = $contact->getSubscriptions();
		$subscription_list = [];

		if ($subscriptions) {
			foreach ($subscriptions as $subscription) {
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

		$attributes = $contact->getAttributes();
		$attribute_list = [];

		if ($attributes) {
			foreach ($attributes as $attribute) {
				$attribute_list[$attribute->getAttribute()->getName()] = $attribute->getValue();
			}
		}

		return [
			'uuid'          => $contact->getUuid(),
			'email'         => $contact->getEmail(),
			'first_name'    => isset($attribute_list['firstname']) ? $attribute_list['firstname'] : null,
			'last_name'     => isset($attribute_list['lastname']) ? $attribute_list['lastname'] : null,
			'subscriptions' => isset($subscription_list) ? $subscription_list : [],
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
	 * @return array|null
	 */
	public function get_contact_by_wp_id(string $wp_user_id, bool $create = true)
	{
		try {
			$client = $this->init_client();

			if (! $client) {
				return null;
			}

			$wp_user = get_user_by('id', $wp_user_id);
			if (! $wp_user) {
				$this->logger->error('WordPress user not found', ['wp_user_id' => $wp_user_id]);
				return null;
			}

			if ($create) {
				$contact = Contact::findOrCreate(['email' => $wp_user->user_email]);
			} else {
				$contact = Contact::findOneBy(['email' => $wp_user->user_email]);
			}

			if (! $contact) {
				$this->logger->error('Failed to find or create contact', [
					'wp_user_id' => $wp_user_id,
					'email' => $wp_user->user_email
				]);
				return null;
			}

			return $this->format_contact($contact);
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Contact By WP ID Error');
			return null;
		}
	}

	/**
	 * Get a contact by email.
	 *
	 * @param string $email The contact's email address.
	 * @return array|null
	 */
	public function get_contact_by_email(string $email)
	{
		try {
			$client = $this->init_client();

			if (! $client) {
				return null;
			}

			$contact = Contact::findOneBy(['email' => $email]);

			if (! $contact) {
				$this->logger->error('Contact not found', ['email' => $email]);
				return null;
			}

			return $this->format_contact($contact);
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Contact By Email Error');
			return null;
		}
	}

	/**
	 * Get a contact by UUID.
	 *
	 * @param string $uuid The contact UUID.
	 * @return array|null
	 */
	public function get_contact_by_uuid(string $uuid)
	{
		try {
			$client = $this->init_client();

			if (! $client) {
				return null;
			}

			$contact = Contact::get($uuid);

			if (! $contact) {
				$this->logger->error('Contact not found', ['uuid' => $uuid]);
				return null;
			}

			return $this->format_contact($contact);
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Contact By UUID Error');
			return null;
		}
	}

	/**
	 * Find or create WP user by UUID.
	 *
	 * @param string $uuid The contact UUID.
	 * @return \WP_User|null
	 */
	public function find_or_create_wp_user_by_uuid(string $uuid, bool $send_notification = false)
	{
		try {
			$contact = $this->get_contact_by_uuid($uuid);
			$contact_email = $contact['email'] ?? null;

			if (!$contact) {
				throw new \Exception('No contact found for uuid ' . $uuid);
			}

			/**
			 * Silently create a new user, without sending notification emails.
			 *
			 * @var \WP_User|WP_Error
			 */
			$existing_wp_user = get_user_by('email', $contact['email']);

			if ($existing_wp_user) {
				return $existing_wp_user;
			} else {
				if ($contact_email) {
					/**
					 * Silently create a new user, without sending notification emails.
					 *
					 * @var \WP_User|WP_Error
					 */
					$new_wp_user = Users::create_woocommerce_user_from_email($contact_email, $send_notification, [
						'first_name' => $contact['first_name'],
						'last_name' => $contact['last_name'],
						'username' => isset($contact['first_name']) && isset($contact['last_name']) ? $contact['first_name'] . $contact['last_name'] : Users::create_username_from_email($contact_email),
					]);

					$this->logger->info('Created new user for email ' . $contact_email, [
						'new_wp_user' => $new_wp_user,
					]);

					if (is_wp_error($new_wp_user)) {
						$err = $new_wp_user->get_error_message();

						throw new \Exception('Failed to create user for voucher: ' . $err);
					}

					return $new_wp_user;
				} else {
					throw new \Exception('No contact email found for voucher');
				}
			}
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Contact Create Error');

			throw $th;
		}
	}

	/**
	 * Create a new contact.
	 *
	 * @param string $email The contact email.
	 *
	 * @return array|null
	 * @throws \Exception If the contact creation fails.
	 * @throws \Throwable If an unexpected error occurs.
	 */
	public function find_or_create_contact(string $email)
	{
		$client = $this->init_client();

		if (! $client) {
			return null;
		}

		try {
			$contact = Contact::findOrCreate(['email' => $email]);

			if (! $contact) {
				throw new \Exception('Contact not found - returned null');
			}

			return $this->format_contact($contact);
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Contact Create Error');

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
	 * @throws \Exception If the contact update fails.
	 */
	public function update_contact(string $id, array $attributes)
	{
		$client = $this->init_client();

		if (! $client) {
			return null;
		}

		try {
			$this->logger->info('Getting contact with ID: ' . $id);
			$contact = Contact::get($id);

			if (! $contact) {
				$this->logger->error('Contact not found with ID: ' . $id);
				return null;
			}

			$contact = Contact::update($id, ['attributes' => $attributes]);

			if (! $contact) {
				throw new \Exception('Contact update failed - returned null');
			}

			return $this->format_contact($contact);
		} catch (\Exception $e) {
			$this->log_exception($e, 'Contact Update Error');

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
	private function format_shop(Shop $shop)
	{
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
	public function get_shops()
	{
		$client = $this->init_client();

		if (! $client) {
			return null;
		}

		try {
			$results = Shop::list();

			if (! $results) {
				return null;
			}

			$shops = [];

			foreach ($results as $shop) {
				$shops[] = $this->format_shop($shop);
			}

			return $shops;
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Shops Error');

			// Check for both PiggyRequestException and GuzzleHttp RequestException
			if (($th instanceof PiggyRequestException && $th->getCode() === 401) ||
				($th instanceof \GuzzleHttp\Exception\RequestException && $th->getResponse() && $th->getResponse()->getStatusCode() === 401)
			) {
				return [
					'error' => true,
					'code' => 'invalid_api_key',
					'message' => __('The API key is invalid or has expired.', 'leat-crm')
				];
			}

			return [
				'error' => true,
				'code' => 'unknown_error',
				'message' => __('An unexpected error occurred while fetching shops.', 'leat-crm')
			];
		}
	}

	/**
	 * Get the shop.
	 *
	 * @param string $id The shop ID.
	 *
	 * @return array|null
	 */
	public function get_shop(string $id)
	{
		$client = $this->init_client();

		if (! $client) {
			return null;
		}

		try {
			$shop = Shop::get($id);

			if (! $shop) {
				return null;
			}

			return $this->format_shop($shop);
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Shop Error');
			return null;
		}
	}

	/**
	 * Get the Rewards response.
	 *
	 * @param Reward $reward The reward object.
	 *
	 * @return array
	 */
	public function format_reward(Reward $reward)
	{
		$media_obj = $reward->getMedia();
		$media     = $media_obj ? [
			'type'  => $media_obj->getType(),
			'value' => $media_obj->getValue(),
		] : null;

		return [
			'uuid'            => $reward->getUuid(),
			'title'           => $reward->getTitle(),
			'required_credits' => $reward->getRequiredCredits(),
			'type'            => $reward->getRewardType(),
			'active'          => $reward->isActive(),
			'attributes'      => $reward->getAttributes(),
			'media'           => $media,
		];
	}

	/**
	 * Format the promotion.
	 *
	 * @param Promotion $promotion The promotion object.
	 *
	 * @return array
	 */
	public function format_promotion(Promotion $promotion)
	{
		// TODO: Missing getMedia() in SDK.
		// $media_obj = $promotion->getMedia();
		// $media     = $media_obj ? [
		// 	'type'  => $media_obj->getType(),
		// 	'value' => $media_obj->getValue(),
		// ] : null;

		return [
			'uuid'               => $promotion->getUuid(),
			'title'              => $promotion->getName(),
			'type'               => $promotion->getType(),
			'redemptions_per_voucher' => $promotion->getRedemptionsPerVoucher(),
			'voucher_limit'       => $promotion->getVoucherLimit(),
			'limit_per_contact'    => $promotion->getLimitPerContact(),
			'expiration_duration' => $promotion->getExpirationDuration(),
			'attributes'         => $promotion->getAttributes(),
		];
	}

	/**
	 * Format the voucher.
	 *
	 * @param Voucher $voucher The voucher object.
	 *
	 * @return array{
	 *   uuid: string,
	 *   code: string,
	 *   status: string,
	 *   contact_uuid: string,
	 *   name: string,
	 *   description: string,
	 *   expiration_date: string,
	 *   activation_date: string,
	 *   redeemed_at: null|\DateTime,
	 *   is_redeemed: boolean,
	 *   promotion: array{uuid: string, name: string},
	 *   custom_attributes: array
	 * }
	 */
	public function format_voucher(Voucher $voucher): array
	{
		return [
			'uuid'              => $voucher->getUuid(),
			'code'              => $voucher->getCode(),
			'status'            => $voucher->getStatus(),
			'contact_uuid'      => $voucher->getContact()?->getUuid(),
			'name'              => $voucher->getName(),
			'description'       => $voucher->getDescription(),
			'expiration_date'   => $voucher->getExpirationDate(),
			'activation_date'   => $voucher->getActivationDate(),
			'redeemed_at'       => $voucher->getRedeemedAt(),
			'is_redeemed'       => $voucher->isRedeemed(),
			'promotion'         => [
				'uuid' => $voucher->getPromotion()?->getUuid(),
				'name' => $voucher->getPromotion()?->getName(),
			],
			'custom_attributes' => $voucher->getAttributes(),
		];
	}

	/**
	 * Get the rewards.
	 *
	 * @return array|null
	 */
	public function get_rewards()
	{
		$client = $this->init_client();

		if (! $client) {
			return null;
		}

		try {
			$results = Reward::list();

			if (! $results) {
				return null;
			}

			$rewards = [];

			foreach ($results as $reward) {
				$rewards[] = $this->format_reward($reward);
			}

			return $rewards;
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Rewards Error');
			return null;
		}
	}

	/**
	 * Get the rewards for contact
	 *
	 * @param string $contact_uuid The contact UUID.
	 *
	 * @return array|null
	 */
	public function get_rewards_for_contact(string $contact_uuid)
	{
		$client = $this->init_client();

		if (! $client) {
			return null;
		}

		try {
			$results = Reward::list([
				'contact_uuid' => $contact_uuid,
			]);

			if (! $results) {
				return null;
			}

			$rewards = [];

			foreach ($results as $reward) {
				$rewards[] = $this->format_reward($reward);
			}

			return $rewards;
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Rewards Error');
			return null;
		}
	}

	public function get_promotions()
	{
		$client = $this->init_client();

		if (! $client) {
			return null;
		}

		try {
			$results = Promotion::list();

			$promotions = array();

			foreach ($results as $promotion) {
				$promotions[] = $this->format_promotion($promotion);
			}

			return $promotions;
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Get Promotions Error');
			return null;
		}
	}

	public function apply_credits(string $contact_uuid, ?int $credits = null, ?float $unit_value = null, ?string $unit_name = null)
	{
		$client = $this->init_client();
		if (! $client) {
			return false;
		}

		$shop_uuid = get_option('leat_shop_uuid', null);
		if (! $shop_uuid) {
			return false;
		}

		$params = [
			'shop_uuid'    => $shop_uuid,
			'contact_uuid' => $contact_uuid,
		];

		if (null !== $credits) {
			$params['credits'] = $credits;
		}

		if (null !== $unit_value) {
			$params['unit_value'] = $unit_value;
		}

		if (null !== $unit_name) {
			$params['unit_name'] = $unit_name;
		}

		// Ensure that either credits or unit_value is set.
		if (! isset($params['credits']) && ! isset($params['unit_value'])) {
			return false;
		}

		try {
			$reception = CreditReception::create($params);
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Apply Credits Error');
			return false;
		}

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
		$uuid = get_user_meta($wp_id, 'leat_uuid', true);

		if (! $uuid && $create) {
			try {
				$contact = $this->find_or_create_contact(get_the_author_meta('email', $wp_id));
				$uuid    = $contact['uuid'];

				$this->sync_user_attributes($wp_id, $uuid);
			} catch (\Throwable $th) {
				$this->log_exception($th, 'Get Contact UUID by WP ID Error');
				return null;
			}

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
	private function get_woocommerce_user_data($user_id)
	{
		if (! function_exists('wc_get_customer_total_spent') || ! function_exists('wc_get_customer_order_count')) {
			return $this->get_default_wc_attributes();
		}

		$user = get_user_by('id', $user_id);
		if (! $user) {
			return $this->get_default_wc_attributes();
		}

		$total_spent  = wc_get_customer_total_spent($user_id);
		$orders_count = wc_get_customer_order_count($user_id);
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
		$last_order_id     = 0;


		if (! empty($customer_orders)) {
			$last_order        = $customer_orders[0];
			$last_order_amount = $last_order->get_total();
			$last_order_date   = $last_order->get_date_created()->format('Y-m-d H:i:s');
			$last_order_id     = $last_order->get_id();
		}

		$currency         = strtolower(get_woocommerce_currency());
		$first_order_date = $this->get_first_order_date($user_id);

		return [
			'wp_wc_total_spent_' . $currency         => (float) $total_spent,
			'wp_wc_orders_count'                     => (int) $orders_count,
			'wp_create_date'                         => $create_date,
			'wp_wc_last_order_amount_' . $currency   => (float) $last_order_amount,
			'wp_wc_last_order_date'                  => $last_order_date,
			'wp_wc_last_order_id'                    => $last_order_id,
			'wp_wc_average_order_value_' . $currency => $orders_count > 0 ? round($total_spent / $orders_count, 2) : 0,
			'wp_wc_first_order_date'                 => $first_order_date,
			'wp_wc_product_categories_purchased'     => $this->get_purchased_categories($user_id),
			'wp_wc_total_products_purchased'         => $this->get_total_products_purchased($user_id),
		];
	}

	private function get_default_wc_attributes()
	{
		$currency = strtolower(get_woocommerce_currency());
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

	private function get_first_order_date($user_id)
	{
		$customer_orders = wc_get_orders(
			[
				'customer' => $user_id,
				'limit'    => 1,
				'orderby'  => 'date',
				'order'    => 'ASC',
			]
		);

		if (! empty($customer_orders)) {
			$first_order = $customer_orders[0];
			return $first_order->get_date_created()->format('Y-m-d H:i:s');
		}

		return '';
	}

	private function get_purchased_categories($user_id, $current_order = null)
	{
		$categories = [];

		// Handle current order if provided (for guests).
		if ($current_order) {
			foreach ($current_order->get_items() as $item) {
				/**
				 * Process each product
				 *
				 * @var \WC_Order_Item_Product $item
				 */
				$product = $item->get_product();

				if ($product) {
					$categories = array_merge($categories, $product->get_category_ids());
				}
			}

			// Get previous orders excluding current order.
			$customer_orders = wc_get_orders(
				[
					'customer' => $current_order->get_billing_email(),
					'status'   => ['completed', 'processing', 'on-hold'],
					'limit'    => -1,
					'exclude'  => [$current_order->get_id()],
				]
			);
		} else {
			// Get all orders for registered user.
			$customer_orders = wc_get_orders(['customer' => $user_id]);
		}

		// Process historical orders.
		foreach ($customer_orders as $order) {
			foreach ($order->get_items() as $item) {
				/**
				 * Process each product
				 *
				 * @var \WC_Order_Item_Product $item
				 */
				$product = $item->get_product();
				if ($product) {
					$categories = array_merge($categories, $product->get_category_ids());
				}
			}
		}

		// Convert to strings and ensure unique values.
		return array_map('strval', array_unique($categories));
	}

	private function get_total_products_purchased($user_id)
	{
		$total_products  = 0;
		$customer_orders = wc_get_orders(['customer' => $user_id]);

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
		$user       = get_userdata($user_id);

		$attributes = [
			'wp_user_id'          => $user_id,
			'firstname'           => $user->first_name,
			'lastname'            => $user->last_name,
			'wp_user_role'        => implode(', ', $user->roles),
			'wp_account_age_days' => floor((time() - strtotime($user->user_registered)) / (60 * 60 * 24)),
			'wp_last_login'       => get_user_meta($user_id, 'leat_last_login', true) ?: '',
			'wp_post_count'       => count_user_posts($user_id),
			'wp_multisite_blogs' => is_multisite() ? $this->get_multisite_blogs($user_id) : '',
		];

		$wc_attributes = $this->get_woocommerce_user_data($user_id);

		$attributes = array_merge($attributes, $wc_attributes);

		$this->ensure_custom_attributes_exist();

		return $attributes;
	}

	private function get_multisite_blogs($user_id)
	{
		$blogs = get_blogs_of_user($user_id);

		$blog_ids = array_map(function ($blog) {
			return $blog->userblog_id;
		}, $blogs);

		return implode(',', $blog_ids);
	}

	private function attribute_exists($attributes_list, $name)
	{
		foreach ($attributes_list as $attribute) {
			if ($attribute->getName() === $name) {
				return true;
			}
		}
		return false;
	}

	private function get_product_categories_options()
	{
		$categories = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);
		$options    = [];

		foreach ($categories as $category) {
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
	private function ensure_custom_attributes_exist()
	{
		$client = $this->init_client();

		if (! $client) {
			return;
		}

		try {
			$attributes_list = CustomAttribute::list(['entity' => 'contact']);
			$currency        = strtolower(get_woocommerce_currency());

			// Define required attributes with proper format.
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
					'name'   => 'wp_multisite_blogs',
					'label'  => 'WordPress Multisite Blogs',
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
					'label'  => 'WooCommerce Total Spent (' . strtoupper($currency) . ')',
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
					'label'  => 'WooCommerce Last Order Amount (' . strtoupper($currency) . ')',
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
					'name'   => 'wp_wc_last_order_id',
					'label'  => 'WooCommerce Last Order ID',
					'type'   => 'number',
				],
				[
					'entity' => 'contact',
					'name'   => 'wp_wc_average_order_value_' . $currency,
					'label'  => 'WooCommerce Average Order Value (' . strtoupper($currency) . ')',
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
						function ($option) {
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

			foreach ($required_attributes as $attr) {
				if (! $this->attribute_exists($attributes_list, $attr['name'])) {
					try {
						// Create single attribute at a time.
						$response = ApiClient::post(CustomAttribute::resourceUri, $attr);
					} catch (\Exception $e) {
						$this->log_exception($e, 'Attribute "' . $attr['name'] . '" Create Error');
					}
				}
			}
		} catch (\Exception $e) {
			$this->log_exception($e, 'Ensure Custom Attributes Error');
		}
	}

	/**
	 * Sync user/guest attributes with Leat, ensuring categories are up to date
	 */
	private function sync_attributes_with_category_update($uuid, $attributes)
	{
		try {
			$this->ensure_custom_attributes_exist();

			$update_result = $this->update_contact($uuid, $attributes);

			if (null === $update_result) {
				$this->logger->error('Update contact returned null');
				return false;
			}

			return $update_result;
		} catch (\Exception $e) {
			$this->log_exception($e, 'Sync Attributes with Category Update Error');

			return false;
		}
	}

	/**
	 * Update sync_user_attributes to use the new method.
	 *
	 * @param int    $user_id
	 * @param string $uuid
	 * @return bool
	 * @throws \Exception If the user is not found.
	 */
	public function sync_user_attributes($user_id, $uuid)
	{
		try {
			$user = get_userdata($user_id);

			if (! $user) {
				throw new \Exception('User not found');
			}

			$attributes = $this->get_user_attributes($user_id);
			return $this->sync_attributes_with_category_update($uuid, $attributes);
		} catch (\Exception $e) {
			$this->log_exception($e, 'Sync User Attributes Error');

			return false;
		}
	}

	public function sync_basic_attributes_from_order($order, $uuid, $is_guest)
	{
		try {
			if ($is_guest) {
				$attributes = [
					'firstname'    => $order->get_billing_first_name(),
					'lastname'     => $order->get_billing_last_name(),
					'wp_user_role' => 'guest',
				];
			} else {
				$user_id = $order->get_user_id();
				$user    = get_userdata($user_id);

				$attributes = [
					'wp_user_id'          => $user_id,
					'firstname'           => $user->first_name,
					'lastname'            => $user->last_name,
					'wp_user_role'        => implode(', ', $user->roles),
					'wp_account_age_days' => floor((time() - strtotime($user->user_registered)) / (60 * 60 * 24)),
					'wp_last_login'       => get_user_meta($user_id, 'leat_last_login', true) ?: '',
					'wp_post_count'       => count_user_posts($user_id),
				];
			}

			return $this->sync_attributes_with_category_update($uuid, $attributes);
		} catch (\Exception $e) {
			$this->log_exception($e, 'Sync Basic Attributes from Order Error');
			return false;
		}
	}

	/**
	 * Update sync_guest_attributes to use the new method.
	 *
	 * @param \WC_Order $order
	 * @param string    $uuid
	 * @return bool
	 * @throws \Exception If the order is not found.
	 */
	public function sync_guest_attributes($order, $uuid)
	{
		try {
			$email = $order->get_billing_email();
			if (! $email) {
				throw new \Exception('No email provided for guest order');
			}

			$attributes = $this->get_woocommerce_guest_data($email, $order);
			return $this->sync_attributes_with_category_update($uuid, $attributes);
		} catch (\Exception $e) {
			$this->log_exception($e, 'Sync Guest Attributes Error');
			return false;
		}
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

		$leat_meta_data = array_filter(
			$meta_data,
			function ($key) {
				return strpos($key, 'leat_') === 0;
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
	public function get_user_reward_logs($wp_user_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'leat_reward_logs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}leat_reward_logs WHERE wp_user_id = %d", $wp_user_id);

		$cache_key   = 'leat_reward_logs_' . $wp_user_id;
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
	public function add_reward_log($wp_user_id, $earn_rule_id, $credits)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'leat_reward_logs';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$data = [
			'wp_user_id'   => $wp_user_id,
			'earn_rule_id' => $earn_rule_id,
			'credits'      => $credits,
			'timestamp'    => current_time('mysql', 1),
		];

		$format = [
			'%d',
			'%d',
			'%d',
			'%s',
		];

		$inserted = $wpdb->insert($table_name, $data, $format);
		// phpcs:enable

		// Clear cache after inserting new log.
		wp_cache_delete('leat_reward_logs_' . $wp_user_id);

		return false !== $inserted;
	}

	public function create_reward_reception($contact_uuid, $reward_uuid)
	{
		$client = $this->init_client();
		if (! $client) {
			return false;
		}

		$shop_uuid = get_option('leat_shop_uuid', null);

		if (! $shop_uuid) {
			$this->logger->error('Shop UUID not set. Unable to create Reward Reception.');

			return;
		}

		try {
			$reception = RewardReception::create(
				[
					'contact_uuid' => $contact_uuid,
					'reward_uuid'  => $reward_uuid,
					'shop_uuid'    => $shop_uuid,
				]
			);

			return $reception ?: false;
		} catch (\Throwable $th) {
			$this->log_exception($th, 'Create Reward Reception Error');
			return false;
		}
	}

	public function refund_credits_full($credit_reception_uuid)
	{
		$client = $this->init_client();
		if (! $client) {
			return false;
		}

		try {
			$refund_result = CreditReception::reverse($credit_reception_uuid);

			if (! $refund_result) {
				$this->logger->error("Failed to process full refund for credit reception: $credit_reception_uuid");
				return false;
			}

			return $refund_result;
		} catch (\Exception $e) {
			$this->log_exception($e, 'Error Processing Full Refund');
			return false;
		}
	}

	public function refund_credits_partial($credit_reception_uuid, $refund_percentage, $original_credits)
	{
		$client = $this->init_client();
		if (! $client) {
			return false;
		}

		try {
			$credits_to_refund = round($original_credits * $refund_percentage);

			$refund_result = CreditReception::create(
				[
					'credits' => -$credits_to_refund,
				]
			);

			if (! $refund_result) {
				$this->logger->error("Failed to process partial refund for credit reception: $credit_reception_uuid");
				return false;
			}

			return $refund_result;
		} catch (\Exception $e) {
			$this->log_exception($e, 'Error Processing Partial Refund');
			return false;
		}
	}

	/**
	 * Get WooCommerce guest data from order history
	 *
	 * @param string   $email Customer email.
	 * @param WC_Order $current_order Current order being processed.
	 * @return array
	 */
	private function get_woocommerce_guest_data($email, $current_order)
	{
		$currency = strtolower(get_woocommerce_currency());

		// Get guest's order history - exclude current order.
		$customer_orders = wc_get_orders(
			[
				'customer' => $email,
				'status'   => ['completed', 'processing', 'on-hold'],
				'limit'    => -1,
				'exclude'  => [$current_order->get_id()], // Explicitly exclude current order.
			]
		);

		$this->logger->info('Found ' . count($customer_orders) . ' previous orders for guest email: ' . $email);

		$total_spent    = $current_order->get_total(); // Start with current order.
		$total_products = 0;
		$orders_count   = 1; // Start with 1 for current order.

		// Set initial dates from current order.
		$current_order_date = $current_order->get_date_created()->format('Y-m-d H:i:s');
		$first_order_date   = $current_order_date;
		$last_order_date    = $current_order_date;
		$last_order_amount  = $current_order->get_total();

		$average_order_value = $orders_count > 0 ? $total_spent / $orders_count : 0;

		return [
			'wp_wc_total_spent_' . $currency         => (float) $total_spent,
			'wp_wc_orders_count'                     => (int) $orders_count,
			'wp_wc_last_order_amount_' . $currency   => (float) $last_order_amount,
			'wp_wc_last_order_date'                  => $last_order_date,
			'wp_wc_last_order_id'                    => $current_order->get_id(),
			'wp_wc_average_order_value_' . $currency => (float) $average_order_value,
			'wp_wc_first_order_date'                 => $first_order_date,
			'wp_wc_product_categories_purchased'     => $this->get_purchased_categories(null, $current_order),
			'wp_wc_total_products_purchased'         => (int) $total_products,
			'wp_user_id'                             => $current_order->get_user_id(),
			'firstname'                              => $current_order->get_billing_first_name(),
			'lastname'                               => $current_order->get_billing_last_name(),
			'wp_user_role'                           => 'guest',
		];
	}

	/**
	 * List all gift card programs.
	 *
	 * @return array|null
	 * @throws \Exception If the client is not initialized.
	 */
	public function list_giftcard_programs()
	{
		$client = $this->init_client();
		if (! $client) {
			return null;
		}

		try {
			$response = GiftcardProgram::list();

			$programs = [];

			foreach ($response as $program) {
				$programs[] = $this->format_giftcard_program($program);
			}

			return $programs;
		} catch (\Exception $e) {
			$this->log_exception($e, 'List Giftcard Programs Error');

			throw $e;
		}
	}

	private function format_giftcard_program(GiftcardProgram $program): array
	{
		return [
			'uuid'                => $program->getUuid(),
			'name'                => $program->getName(),
			'active'              => $program->isActive(),
			'max_amount_in_cents' => $program->getMaxAmountInCents(),
			'calculator_flow'     => $program->getCalculatorFlow(),
			'expiration_days'     => $program->getExpirationDays(),
		];
	}

	public function create_giftcard($giftcard_program_uuid)
	{
		$client = $this->init_client();
		if (! $client) {
			return null;
		}

		try {
			$response = Giftcard::create(
				[
					'type'                  => 1,
					'giftcard_program_uuid' => $giftcard_program_uuid,
				]
			);

			return $this->format_giftcard($response);
		} catch (\Exception $e) {
			$this->log_exception($e, 'Create Giftcard Error');
			throw $e;
		}
	}

	public function create_giftcard_transaction($giftcard_uuid, $amount_in_cents)
	{
		$client = $this->init_client();
		if (! $client) {
			return null;
		}

		$shop_uuid = get_option('leat_shop_uuid', null);
		if (! $shop_uuid) {
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

		return $this->format_giftcard_transaction($response);
	}

	private function format_giftcard(Giftcard $giftcard)
	{
		return [
			'id'   => $giftcard->getId(),
			'uuid' => $giftcard->getUuid(),
			'hash' => $giftcard->getHash(),
		];
	}

	private function format_giftcard_transaction(GiftcardTransaction $giftcard_transaction)
	{
		return [
			'id'              => $giftcard_transaction->getId(),
			'uuid'            => $giftcard_transaction->getUuid(),
			'amount_in_cents' => $giftcard_transaction->getAmountInCents(),
		];
	}

	public function send_giftcard_email($giftcard_uuid, $contact_uuid, $email_uuid = null, $merge_tags = [])
	{
		$client = $this->init_client();

		if (! $client) {
			return false;
		}

		try {
			$payload = [
				'contact_uuid' => $contact_uuid,
			];

			if ($email_uuid) {
				$payload['email_uuid'] = $email_uuid;
			}

			if (! empty($merge_tags)) {
				// Ensure merge tags are prefixed with 'custom.'.
				$formatted_tags = [];

				foreach ($merge_tags as $key => $value) {
					$key                    = strpos($key, 'custom.') === 0 ? $key : 'custom.' . $key;
					$formatted_tags[$key] = $value;
				}
				$payload['merge_tags'] = $formatted_tags;
			}

			// TODO: this endpoint is not in the SDK yet.
			$response = ApiClient::post("/api/v3/oauth/clients/giftcards/{$giftcard_uuid}/send-by-email", $payload);

			$this->logger->info(
				'Giftcard email sent successfully',
				[
					'giftcard_uuid' => $giftcard_uuid,
					'contact_uuid'  => $contact_uuid,
					'response'      => $response,
				]
			);

			return $response;
		} catch (\Exception $e) {
			$this->log_exception($e, 'Send Giftcard Email Error');
			return false;
		}
	}

	/**
	 * Reverse a giftcard transaction (full refund)
	 *
	 * @param string $transaction_uuid UUID of the transaction to reverse.
	 * @return array|false The reversed transaction data or false on failure.
	 */
	public function reverse_giftcard_transaction($transaction_uuid)
	{
		$client = $this->init_client();
		if (! $client) {
			return false;
		}

		try {
			$response = GiftcardTransaction::reverse($transaction_uuid);

			return $this->format_giftcard_transaction($response);
		} catch (\Exception $e) {
			$this->log_exception($e, 'Reverse Giftcard Transaction Error');
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
	public function create_giftcard_refund_transaction($giftcard_uuid, $amount_in_cents)
	{
		if ($amount_in_cents >= 0) {
			// Ensure amount is negative for refunds.
			$amount_in_cents = -$amount_in_cents;
		}

		try {
			return $this->create_giftcard_transaction($giftcard_uuid, $amount_in_cents);
		} catch (\Exception $e) {
			$this->log_exception($e, 'Create Giftcard Refund Transaction Error');
			return false;
		}
	}

	/**
	 * Get items for sync
	 *
	 * @param string $action_name The action name.
	 * @return array|false The items or false on failure.
	 * @throws \Exception If the action name is invalid.
	 */
	public function get_items_for_sync($action_name)
	{
		switch ($action_name) {
			case 'sync_promotions':
				return $this->get_promotions();
			case 'sync_spend_rules':
				return $this->get_rewards();
			default:
				throw new \Exception("Invalid action name: {$action_name}");
		}
	}
}
