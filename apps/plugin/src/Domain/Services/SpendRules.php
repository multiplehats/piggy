<?php

namespace Leat\Domain\Services;

use Leat\Utils\Coupons;
use Leat\Utils\Logger;

/**
 * Handles spend rule management and operations.
 *
 * This class manages spend rules for the loyalty program, including creation,
 * retrieval, formatting, and coupon generation for spend rules.
 *

 * @package Leat\Domain\Services
 * @internal
 */
class SpendRules
{
	/**
	 * Logger instance for debugging and error tracking.
	 *

	 * @var Logger
	 */
	private $logger;

	/**
	 * Initializes a new instance of the SpendRules class.
	 *

	 */
	public function __construct()
	{
		$this->logger = new Logger();
	}

	/**
	 * Retrieves post meta data with fallback value support.
	 *

	 * @param int    $post_id        The post ID to retrieve meta for.
	 * @param string $key            The meta key to retrieve.
	 * @param mixed  $fallback_value Optional. Value to return if meta is empty.
	 * @return mixed The meta value or fallback value if empty.
	 */
	private function get_post_meta_data($post_id, $key, $fallback_value = null)
	{
		$value = get_post_meta($post_id, $key, true);
		return empty($value) ? $fallback_value : $value;
	}

	/**
	 * Retrieves spend rules filtered by type.
	 *

	 * @param string|null $type        Optional. The type of spend rule to filter by.
	 * @param array      $post_status Optional. Array of post statuses to include. Default ['publish'].
	 * @return array|null Array of formatted spend rules or null if none found.
	 */
	public function get_spend_rules_by_type($type, $post_status = ['publish'])
	{
		$args = [
			'post_type'        => 'leat_spend_rule',
			'post_status'      => $post_status,
			'suppress_filters' => false,
		];

		if ($type) {
			$args['meta_query'] = [
				[
					'key'   => '_leat_spend_rule_type',
					'value' => $type,
				],
			];
		}

		$cache_key = 'leat_spend_rules_' . md5(wp_json_encode($args));
		$posts     = wp_cache_get($cache_key);

		if (false === $posts) {
			$posts = get_posts($args);
			wp_cache_set($cache_key, $posts, '', 3600);
		}

		if (empty($posts)) {
			return null;
		}

		$posts = array_map([$this, 'get_formatted_post'], $posts);

		return $posts;
	}

	/**
	 * Retrieves a spend rule by its ID.
	 *

	 * @param int $id The spend rule post ID.
	 * @return array|null Formatted spend rule data or null if not found.
	 */
	public function get_by_id($id)
	{
		$post = get_post($id);

		if (empty($post)) {
			return null;
		}

		return $this->get_formatted_post($post);
	}

	/**
	 * Formats a spend rule post for API response.
	 *
	 * Converts a WordPress post object into a structured array containing
	 * all spend rule settings and metadata.
	 *

	 * @param \WP_Post $post The spend rule post object to format.
	 * @return array Formatted spend rule data.
	 */
	public function get_formatted_post($post)
	{
		$type = $this->get_post_meta_data($post->ID, '_leat_spend_rule_type', null);

		$spend_rule = [
			'id'                 => (int) $post->ID,
			'createdAt'          => $post->post_date,
			'updatedAt'          => $post->post_modified,
			'status'             => [
				'id'          => 'status',
				'label'       => __('Status', 'leat-crm'),
				'default'     => 'publish',
				'value'       => $post->post_status,
				'options'     => [
					'publish' => ['label' => __('Active', 'leat-crm')],
					'draft'   => ['label' => __('Inactive', 'leat-crm')],
				],
				'type'        => 'select',
				'description' => __('Set the status of the rule. Inactive spend rules will not be displayed to users.', 'leat-crm'),
			],
			'title'              => [
				'id'          => 'title',
				'label'       => __('Title', 'leat-crm'),
				'default'     => null,
				'value'       => $post->post_title,
				'type'        => 'text',
				'description' => __('This is not displayed to the user and is only used for internal reference. You can manage this in the Leat dashboard.', 'leat-crm'),
			],
			'type'               => [
				'id'          => 'type',
				'label'       => __('Type', 'leat-crm'),
				'default'     => 'FREE_PRODUCT',
				'value'       => $type,
				'type'        => 'select',
				'options'     => [
					'FREE_PRODUCT'   => ['label' => __('Free / Discounted Product', 'leat-crm')],
					'ORDER_DISCOUNT' => ['label' => __('Order Discount', 'leat-crm')],
					'FREE_SHIPPING'  => ['label' => __('Free Shipping', 'leat-crm')],
					'CATEGORY'       => ['label' => __('Category Discount', 'leat-crm')],
				],
				'description' => __('The type of spend rule.', 'leat-crm'),
			],
			'startsAt'           => [
				'id'          => 'starts_at',
				'label'       => __('Starts at', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_starts_at', null),
				'type'        => 'date',
				'description' => __('Optional date for when the rule should start.', 'leat-crm'),
			],
			'expiresAt'          => [
				'id'          => 'expires_at',
				'label'       => __('Expires at', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_expires_at', null),
				'type'        => 'date',
				'description' => __('Optional date for when the rule should expire.', 'leat-crm'),
			],
			'completed'          => $this->get_post_meta_data($post->ID, '_leat_spend_rule_completed', null),
			'creditCost'         => [
				'id'          => 'credit_cost',
				'label'       => __('Credit cost', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_credit_cost', null),
				'type'        => 'number',
				'description' => __('The amount of credits it will cost to redeem the reward. This is managed in the Leat dashboard.', 'leat-crm'),
			],
			'selectedReward'     => [
				'id'          => 'selected_reward',
				'label'       => __('Selected reward', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_selected_reward', null),
				'type'        => 'text',
				'description' => __('The reward that is selected for the spend rule.', 'leat-crm'),
			],
			'image'              => [
				'id'          => 'image',
				'label'       => __('Image', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_image', null),
				'type'        => 'text',
				'description' => __('The image that is displayed for the spend rule.', 'leat-crm'),
			],
			'description'        => [
				'id'          => 'description',
				'label'       => __('Description', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_description', null),
				'type'        => 'translatable_text',
				'description' => $this->get_description_placeholder($type),
			],
			'instructions'       => [
				'id'          => 'instructions',
				'label'       => __('Instructions', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_instructions', null),
				'type'        => 'translatable_text',
				'description' => $this->get_instructions_placeholder($type),
			],
			'fulfillment'        => [
				'id'          => 'fulfillment',
				'label'       => __('Fulfillment description', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_fulfillment', null),
				'type'        => 'translatable_text',
				'description' => $this->get_fulfillment_placeholder($type),
			],
			'leatRewardUuid'     => [
				'id'          => 'leat_reward_uuid',
				'label'       => __('Leat Reward UUID', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_reward_uuid', null),
				'type'        => 'text',
				'description' => __('The UUID of the corresponding Leat reward.', 'leat-crm'),
			],
			'selectedCategories' => [
				'id'          => 'selected_categories',
				'label'       => __('Selected category', 'leat-crm'),
				'default'     => [],
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_selected_categories', []),
				'type'        => 'categories_select',
				'description' => __('The category that the user can spent their credits in.', 'leat-crm'),
			],
			'limitUsageToXItems' => [
				'id'          => 'limit_usage_to_x_items',
				'label'       => __('Limit usage to X items', 'leat-crm'),
				'default'     => 1,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_limit_usage_to_x_items', 0),
				'type'        => 'number',
				'description' => __('Limit the discount to a specific number of items. Set to 0 for unlimited. If you set it to 0 be aware that this will allow the customer to use the discount on all items in the cart.', 'leat-crm'),
			],
		];

		$spend_rule['label'] = [
			'id'          => 'label',
			'label'       => __('Label', 'leat-crm'),
			'default'     => $this->get_default_label($type),
			'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_label'),
			'type'        => 'translatable_text',
			'description' => $this->get_label_description($type),
		];

		$spend_rule['selectedProducts'] = [
			'id'          => 'selected_products',
			'label'       => __('Selected products', 'leat-crm'),
			'default'     => [],
			'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_selected_products', []),
			'type'        => 'products_select',
			'description' => __('The products that are selected for the spend rule.', 'leat-crm'),
		];

		$spend_rule['discountValue'] = [
			'id'          => 'discount_value',
			'label'       => __('Discount value', 'leat-crm'),
			'default'     => 10,
			'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_discount_value', null),
			'type'        => 'number',
			'description' => __('The value of the discount.', 'leat-crm'),
		];

		$spend_rule['discountType'] = [
			'id'          => 'discount_type',
			'label'       => __('Discount type', 'leat-crm'),
			'default'     => 'percentage',
			'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_discount_type', 'percentage'),
			'type'        => 'select',
			'options'     => [
				'percentage' => ['label' => __('Percentage', 'leat-crm')],
				'fixed'      => ['label' => __('Fixed amount', 'leat-crm')],
			],
			'description' => __('The type of discount.', 'leat-crm'),
		];

		$spend_rule['minimumPurchaseAmount'] = [
			'id'          => 'minimum_purchase_amount',
			'label'       => __('Minimum purchase amount', 'leat-crm'),
			'default'     => 0,
			'value'       => $this->get_post_meta_data($post->ID, '_leat_spend_rule_minimum_purchase_amount', 0),
			'type'        => 'number',
			'description' => __('The minimum purchase amount required to redeem the reward.', 'leat-crm'),
		];

		return $spend_rule;
	}

	/**
	 * Gets the description text for spend rule labels.
	 *

	 * @param string $type The spend rule type.
	 * @return string The formatted description text with placeholder information.
	 */
	private function get_label_description($type)
	{
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the label text. */
		return sprintf(__("The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat-crm'), $placeholders);
	}

	/**
	 * Gets the default label template for a spend rule type.
	 *

	 * @param string $type The spend rule type.
	 * @return array Default label configuration.
	 */
	private function get_default_label($type)
	{
		return [
			'default' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}',
		];
	}

	/**
	 * Gets the description text for spend rule descriptions.
	 *

	 * @param string $type The spend rule type.
	 * @return string The formatted description text with placeholder information.
	 */
	private function get_description_placeholder($type)
	{
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the description text. */
		return sprintf(__('Add a description of the reward. Available placeholders: %s', 'leat-crm'), $placeholders);
	}

	/**
	 * Gets the instructions text for spend rule instructions.
	 *

	 * @param string $type The spend rule type.
	 * @return string The formatted instructions text with placeholder information.
	 */
	private function get_instructions_placeholder($type)
	{
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the instructions text. */
		return sprintf(__('Add instructions on how to redeem the reward. Available placeholders: %s', 'leat-crm'), $placeholders);
	}

	/**
	 * Gets the fulfillment text for spend rule fulfillments.
	 *

	 * @param string $type The spend rule type.
	 * @return string The formatted fulfillment text with placeholder information.
	 */
	private function get_fulfillment_placeholder($type)
	{
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		/* translators: %s: List of available placeholders that can be used in the fulfillment text. */
		return sprintf(__('Add instructions on how fulfillment will be handled. Available placeholders: %s', 'leat-crm'), $placeholders);
	}

	/**
	 * Deletes a spend rule by its Leat UUID.
	 *

	 * @param string $uuid The Leat reward UUID to find and delete.
	 * @return void
	 */
	public function delete_spend_rule_by_leat_uuid($uuid)
	{
		$posts = get_posts(
			[
				'post_type'              => 'leat_spend_rule',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'meta_key'               => '_leat_reward_uuid',
				'meta_value'             => $uuid,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if (! empty($posts)) {
			wp_delete_post($posts[0]);
		}
	}

	/**
	 * Gets the applicable spend rule for a given credit amount.
	 *
	 * Finds the spend rule with the highest credit cost that is still within
	 * the user's available credit amount.
	 *

	 * @param int $credit_amount The available credit amount.
	 * @return array|null The applicable spend rule, or null if none found.
	 */
	public function get_applicable_spend_rule($credit_amount)
	{
		$spend_rules = $this->get_spend_rules_by_type(null);

		if (! $spend_rules) {
			return null;
		}

		$applicable_rule     = null;
		$highest_credit_cost = 0;

		foreach ($spend_rules as $rule) {
			$credit_cost = $rule['creditCost']['value'] ?? PHP_INT_MAX;

			if ($credit_amount >= $credit_cost && $credit_cost > $highest_credit_cost) {
				$applicable_rule     = $rule;
				$highest_credit_cost = $credit_cost;
			}
		}

		return $applicable_rule;
	}

	/**
	 * Creates or updates a spend rule based on a Leat reward.
	 *

	 * @param array    $reward           The Leat reward data.
	 * @param int|null $existing_post_id Optional. Existing post ID to update.
	 * @return void
	 */
	public function create_or_update_spend_rule_from_reward($reward, $existing_post_id = null)
	{
		$post_data = array(
			'post_type'  => 'leat_spend_rule',
			'post_title' => $reward['title'],
			'meta_input' => array(
				'_leat_spend_rule_credit_cost'     => $reward['required_credits'],
				'_leat_reward_uuid'                => $reward['uuid'],
				'_leat_spend_rule_selected_reward' => $reward['uuid'],
			),
		);

		if (isset($reward['image'])) {
			$post_data['meta_input']['_leat_spend_rule_image'] = $reward['image'];
		}

		if ($existing_post_id) {
			$post_data['ID'] = $existing_post_id;
			wp_update_post($post_data);
		} else {
			$post_data['post_status'] = 'draft';

			$post_data['meta_input']['_leat_spend_rule_type'] = "ORDER_DISCOUNT";

			wp_insert_post($post_data);
		}
	}

	/**
	 * Retrieves a spend rule by its Leat UUID.
	 *

	 * @param string $uuid The Leat reward UUID.
	 * @return array|null Formatted spend rule or null if not found.
	 */
	public function get_spend_rule_by_leat_uuid($uuid)
	{
		$cache_key = 'leat_spend_rule_' . md5($uuid);
		$posts     = wp_cache_get($cache_key);

		if (false === $posts) {
			$posts = get_posts(
				[
					'post_type'      => 'leat_spend_rule',
					'meta_key'       => '_leat_reward_uuid',
					'meta_value'     => $uuid,
					'posts_per_page' => 1,
				]
			);
			wp_cache_set($cache_key, $posts, '', 3600);
		}

		if (! empty($posts)) {
			return $this->get_formatted_post($posts[0]);
		}

		return null;
	}

	/**
	 * Retrieves a formatted spend rule by its post ID.
	 *

	 * @param int $id The post ID.
	 * @return array|null Formatted spend rule or null if not found.
	 */
	public function get_spend_rule_by_id($id)
	{
		$post = get_post($id);

		if (! $post) {
			return null;
		}

		return $this->get_formatted_post($post);
	}

	/**
	 * Converts the discount type value to WooCommerce format.
	 *

	 * @param string $value The discount type value ('percentage' or 'fixed').
	 * @return string|null The WooCommerce discount type or null if invalid.
	 */
	private function get_discount_type($value)
	{
		if ('percentage' === $value) {
			return 'percent';
		} elseif ('fixed' === $value) {
			return 'fixed_product';
		}

		return null;
	}

	/**
	 * Creates a WooCommerce coupon for a spend rule.
	 *

	 * @param array    $formatted_spend_rule The formatted spend rule data.
	 * @param int|null $user_id             Optional. The user ID to restrict the coupon to.
	 * @return string The generated coupon code.
	 */
	public function create_coupon_for_spend_rule($formatted_spend_rule, $user_id)
	{
		$coupon_code = wp_generate_uuid4();

		$existing_coupon = new \WC_Coupon($coupon_code);

		if ($existing_coupon) {
			$coupon_code = wp_generate_uuid4();
		}

		$coupon = new \WC_Coupon();
		$coupon->set_code($coupon_code);
		$coupon->set_description('Leat Spend Rule: ' . $formatted_spend_rule['title']['value']);
		$coupon->set_usage_limit(1);
		$coupon->set_individual_use(true);

		$coupon->add_meta_data('_leat_spend_rule_coupon', 'true', true);
		$coupon->add_meta_data('_leat_spend_rule_id', $formatted_spend_rule['id'], true);

		$discount_type = $this->get_discount_type($formatted_spend_rule['discountType']['value']);
		if ($discount_type) {
			$coupon->set_discount_type($discount_type);
		}

		if ($user_id) {
			$user       = get_user_by('id', $user_id);
			$user_email = $user->user_email;

			$coupon->add_meta_data('_leat_user_id', $user_id, true);
			$coupon->set_email_restrictions([$user_email]);
		}

		switch ($formatted_spend_rule['type']['value']) {
			case 'FREE_PRODUCT':
			case 'ORDER_DISCOUNT':
				$coupon->set_amount(0);
				break;

			case 'FREE_SHIPPING':
				$coupon->set_free_shipping(true);
				break;

			case 'CATEGORY':
				$coupon->set_amount($formatted_spend_rule['discountValue']['value']);

				// Set product categories.
				if (! empty($formatted_spend_rule['selectedCategories']['value'])) {
					$coupon->set_product_categories($formatted_spend_rule['selectedCategories']['value']);
				}

				// Add limit usage to X items if set.
				if (isset($formatted_spend_rule['limitUsageToXItems']['value'])) {
					$limit = $formatted_spend_rule['limitUsageToXItems']['value'];

					if ($limit) {
						$coupon->set_limit_usage_to_x_items(intval($limit));
					} else {
						// By default, always limit usage to 1 item.
						$coupon->set_limit_usage_to_x_items(1);
					}
				}

				break;
		}

		// Check for minimum purchase amount.
		if (
			isset($formatted_spend_rule['minimumPurchaseAmount']) &&
			is_numeric($formatted_spend_rule['minimumPurchaseAmount']['value'])
		) {
			$min_amount = floatval($formatted_spend_rule['minimumPurchaseAmount']['value']);
			if ($min_amount > 0) {
				$coupon->set_minimum_amount($min_amount);
			}
		}

		$coupon->save();

		return $coupon_code;
	}

	/**
	 * Retrieves all valid and usable coupons for a specific user.
	 *

	 * @param int $user_id The user ID.
	 * @return array List of valid coupons with their associated spend rules.
	 */
	public function get_coupons_by_user_id($user_id)
	{
		$user = get_user_by('id', $user_id);

		if (! $user || is_wp_error($user)) {
			return [];
		}

		$coupon_codes = [];
		$coupons = Coupons::find_coupons_by_email($user->user_email);

		foreach ($coupons as $coupon) {
			// Check if coupon has expired
			$expiry_date = $coupon->get_date_expires();
			if ($expiry_date && current_time('timestamp', true) > $expiry_date->getTimestamp()) {
				continue;
			}

			// Skip if usage limit is reached
			if ($coupon->get_usage_limit() > 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit()) {
				continue;
			}

			// Skip if per user usage limit is reached
			if ($coupon->get_usage_limit_per_user() > 0) {
				$used_by = $coupon->get_used_by();
				$user_usage_count = count(array_filter($used_by, function ($user_data) use ($user) {
					return $user_data == $user->ID || $user_data == $user->user_email;
				}));
				if ($user_usage_count >= $coupon->get_usage_limit_per_user()) {
					continue;
				}
			}

			$post_id = get_post_meta($coupon->get_id(), '_leat_spend_rule_id', true);

			if (! $post_id) {
				continue;
			}

			$rule = $this->get_spend_rule_by_id($post_id);

			if (! $rule) {
				continue;
			}

			$coupon_codes[] = array(
				'type' => 'spend_rule',
				'code' => $coupon->get_code(),
				'rule' => $rule,
			);
		}

		return $coupon_codes;
	}
}
