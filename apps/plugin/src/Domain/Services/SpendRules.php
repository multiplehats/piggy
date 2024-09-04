<?php

namespace PiggyWP\Domain\Services;


/**
 * Class SpendRules
 */
class SpendRules
{
	private function get_post_meta_data($post_id, $key, $fallback_value = null)
	{
		$value = get_post_meta($post_id, $key, true);
		return empty($value) ? $fallback_value : $value;
	}

	public function get_spend_rules_by_type($type, $post_status = ['publish'])
	{
		$args = [
			'post_type' => 'piggy_spend_rule',
			'post_status' => $post_status,
		];

		if($type) {
			$args['meta_query'] = [
				[
					'key' => '_piggy_spend_rule_type',
					'value' => $type,
				],
			];
		}

		$posts = get_posts($args);

		if (empty($posts)) {
			return null;
		}

		$posts = array_map([$this, 'get_formatted_post'], $posts);

		return $posts;
	}

	/**
	 * Get a spend rule by its ID.
	 *
	 * @param int $id Spend Rule ID.
	 * @return array|null
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
	 * Convert a Spend Rule post into an object suitable for a WP REST API response.
	 *
	 * @param \WP_Post $post Spend Rule post object.
	 * @return array
	 */
	public function get_formatted_post($post)
	{
		$type = $this->get_post_meta_data($post->ID, '_piggy_spend_rule_type', null);

		$spend_rule = [
			'id' => (int) $post->ID,
			'createdAt' => $post->post_date,
			'updatedAt' => $post->post_modified,
			'status' => [
				'id' => 'status',
				'label' => __('Status', 'piggy'),
				'default' => 'publish',
				'value' => $post->post_status,
				'options' => [
					'publish' => ['label' => __('Active', 'piggy')],
					'draft' => ['label' => __('Inactive', 'piggy')],
				],
				'type' => 'select',
				'description' => __('Set the status of the rule. Inactive spend rules will not be displayed to users.', 'piggy'),
			],
			'title' => [
				'id' => 'title',
				'label' => __('Title', 'piggy'),
				'default' => null,
				'value' => $post->post_title,
				'type' => 'text',
				'description' => __( 'This is not displayed to the user and is only used for internal reference. You can manage this in the Piggy dashboard.', 'piggy' ),
			],
			'type' => [
				'id' => 'type',
				'label' => __('Type', 'piggy'),
				'default' => 'FREE_PRODUCT',
				'value' => $type,
				'type' => 'select',
				'options' => [
					'FREE_PRODUCT' => ['label' => __('Free / Discounted Product', 'piggy')],
					'ORDER_DISCOUNT' => ['label' => __('Order Discount', 'piggy')],
					'FREE_SHIPPING' => ['label' => __('Free Shipping', 'piggy')],
				],
				'description' => __('The type of spend rule.', 'piggy'),
			],
			'startsAt' => [
				'id' => 'starts_at',
				'label' => __('Starts at', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_starts_at', null),
				'type' => 'date',
				'description' => __('Optional date for when the rule should start.', 'piggy'),
			],
			'expiresAt' => [
				'id' => 'expires_at',
				'label' => __('Expires at', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_expires_at', null),
				'type' => 'date',
				'description' => __('Optional date for when the rule should expire.', 'piggy'),
			],
			'completed' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_completed', null),
			'creditCost' => [
				'id' => 'credit_cost',
				'label' => __('Credit cost', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_credit_cost', null),
				'type' => 'number',
				'description' => __('The amount of credits it will cost to redeem the reward. This is managed in the Piggy dashboard.', 'piggy'),
			],
			'selectedReward' => [
				'id' => 'selected_reward',
				'label' => __('Selected reward', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_selected_reward', null),
				'type' => 'text',
				'description' => __('The reward that is selected for the spend rule.', 'piggy'),
			],
			'description' => [
				'id' => 'description',
				'label' => __('Description', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_description', null),
				'type' => 'translatable_text',
				'description' => $this->get_description_placeholder($type),
			],
			'instructions' => [
				'id' => 'instructions',
				'label' => __('Instructions', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_instructions', null),
				'type' => 'translatable_text',
				'description' => $this->get_instructions_placeholder($type),
			],
			'fulfillment' => [
				'id' => 'fulfillment',
				'label' => __('Fulfillment description', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_fulfillment', null),
				'type' => 'translatable_text',
				'description' => $this->get_fulfillment_placeholder($type),
			],
			'piggyRewardUuid' => [
				'id' => 'piggy_reward_uuid',
				'label' => __('Piggy Reward UUID', 'piggy'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_reward_uuid', null),
				'type' => 'text',
				'description' => __('The UUID of the corresponding Piggy reward.', 'piggy'),
			],
		];

		$spend_rule['label'] = [
			'id' => 'label',
			'label' => __('Label', 'piggy'),
			'default' => $this->get_default_label($type),
			'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_label'),
			'type' => 'translatable_text',
			'description' => $this->get_label_description($type),
		];

		$spend_rule['selectedProducts'] = [
			'id' => 'selected_products',
			'label' => __('Selected products', 'piggy'),
			'default' => [],
			'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_selected_products', []),
			'type' => 'products_select',
			'description' => __('The products that are selected for the spend rule.', 'piggy'),
		];

		$spend_rule['discountValue'] = [
			'id' => 'discount_value',
			'label' => __('Discount value', 'piggy'),
			'default' => 10,
			'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_discount_value', null),
			'type' => 'number',
			'description' => __('The value of the discount.', 'piggy'),
		];

		$spend_rule['discountType'] = [
			'id' => 'discount_type',
			'label' => __('Discount type', 'piggy'),
			'default' => 'percentage',
			'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_discount_type', 'percentage'),
			'type' => 'select',
			'options' => [
				'percentage' => ['label' => __('Percentage', 'piggy')],
				'fixed' => ['label' => __('Fixed amount', 'piggy')],
			],
			'description' => __('The type of discount.', 'piggy'),
		];

		$spend_rule['minimumPurchaseAmount'] = [
			'id' => 'minimum_purchase_amount',
			'label' => __('Minimum purchase amount', 'piggy'),
			'default' => 0,
			'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_minimum_purchase_amount', 0),
			'type' => 'number',
			'description' => __('The minimum purchase amount required to redeem the reward.', 'piggy'),
		];


		return $spend_rule;
	}

	private function get_label_description($type)
	{
		$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
		return sprintf(__("The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'piggy'), $placeholders);
	}

	private function get_default_label($type)
	{
		return [
			'en_US' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}'
		];
	}

	private function get_description_placeholder($type)
	{
		$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
		return sprintf(__("Add a description of the reward. Available placeholders: %s", 'piggy'), $placeholders);
	}

	private function get_instructions_placeholder($type)
	{
		$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
		return sprintf(__("Add instructions on how to redeem the reward. Available placeholders: %s", 'piggy'), $placeholders);
	}

	private function get_fulfillment_placeholder($type)
	{
		$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
		return sprintf(__("Add instructions on how fulfillment will be handled. Available placeholders: %s", 'piggy'), $placeholders);
	}

	public function delete_spend_rule_by_piggy_uuid($uuid) {
		$args = array(
			'post_type' => 'piggy_spend_rule',
			'meta_key' => '_piggy_reward_uuid',
			'meta_value' => $uuid,
			'posts_per_page' => 1,
		);

		$posts = get_posts($args);

		if (!empty($posts)) {
			wp_delete_post($posts[0]->ID);
		}
	}

	public function get_all_spend_rules() {
		$args = array(
			'post_type' => 'piggy_spend_rule',
			'posts_per_page' => -1,
			'meta_key' => '_piggy_reward_uuid',
		);

		$posts = get_posts($args);

		$spend_rules = array();
		foreach ($posts as $post) {
			$spend_rules[$post->ID] = array(
				'ID' => $post->ID,
				'_piggy_reward_uuid' => get_post_meta($post->ID, '_piggy_reward_uuid', true),
			);
		}

		return $spend_rules;
	}

	/**
	 * Get the applicable spend rule for a given credit amount.
	 *
	 * @param int $credit_amount The available credit amount.
	 * @return array|null The applicable spend rule, or null if none found.
	 */
	public function get_applicable_spend_rule($credit_amount)
	{
		$spend_rules = $this->get_spend_rules_by_type(null); // Get all spend rules

		if (!$spend_rules) {
			return null;
		}

		$applicable_rule = null;
		$highest_credit_cost = 0;

		foreach ($spend_rules as $rule) {
			$credit_cost = $rule['creditCost']['value'] ?? PHP_INT_MAX;

			if ($credit_amount >= $credit_cost && $credit_cost > $highest_credit_cost) {
				$applicable_rule = $rule;
				$highest_credit_cost = $credit_cost;
			}
		}

		return $applicable_rule;
	}

	public function create_or_update_spend_rule_from_reward($reward) {
		$existing_rule = $this->get_spend_rule_by_piggy_uuid($reward['uuid']);

		$post_data = array(
			'post_type' => 'piggy_spend_rule',
			'post_title' => $reward['title'],
			'post_status' => $reward['active'],
			'meta_input' => array(
				'_piggy_spend_rule_type' => $reward['type'],
				'_piggy_spend_rule_credit_cost' => $reward['requiredCredits'],
				'_piggy_reward_uuid' => $reward['uuid'],
				'_piggy_spend_rule_selected_reward' => $reward['uuid'],
			)
		);

		if ($existing_rule) {
			$post_data['ID'] = $existing_rule['id'];
			wp_update_post($post_data);
		} else {
			// New rules are always draft by default.
			$post_data['post_status'] = 'draft';
			wp_insert_post($post_data);
		}
	}

	public function get_spend_rule_by_piggy_uuid($uuid) {
		$args = array(
			'post_type' => 'piggy_spend_rule',
			'meta_key' => '_piggy_reward_uuid',
			'meta_value' => $uuid,
			'posts_per_page' => 1,
		);

		$posts = get_posts($args);

		if (!empty($posts)) {
			return $this->get_formatted_post($posts[0]);
		}

		return null;
	}

	public function delete_spend_rules_by_uuids($uuids_to_delete) {
		foreach ($uuids_to_delete as $post_id => $uuid) {
			wp_delete_post($post_id, true);
		}
	}

	public function handle_duplicated_spend_rules($uuids) {
		global $wpdb;
		$table_name = $wpdb->postmeta;

		foreach ($uuids as $uuid) {
			$query = $wpdb->prepare(
				"SELECT post_id FROM $table_name WHERE meta_key = '_piggy_reward_uuid' AND meta_value = %s ORDER BY post_id DESC",
				$uuid
			);
			$post_ids = $wpdb->get_col($query);

			if (count($post_ids) > 1) {
				// Keep the most recent one (highest post_id), delete the rest
				$keep_id = array_shift($post_ids);
				foreach ($post_ids as $post_id) {
					wp_delete_post($post_id, true);
				}
			}
		}
	}

	public function delete_spend_rules_with_empty_uuid() {
		$args = array(
			'post_type' => 'piggy_spend_rule',
			'posts_per_page' => -1,
			'post_status' => array('publish', 'draft'),
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_piggy_reward_uuid',
					'value' => '',
					'compare' => '='
				),
				array(
					'key' => '_piggy_reward_uuid',
					'compare' => 'NOT EXISTS'
				)
			)
		);

		$posts = get_posts($args);

		foreach ($posts as $post) {
			wp_delete_post($post->ID, true);
		}

		return count($posts); // Return the number of deleted posts
	}

	public function get_spend_rule_by_id($id) {
		$post = get_post($id);

		if (!$post) {
			return null;
		}

		return $this->get_formatted_post($post);
	}

	public function create_coupon_for_spend_rule( $formatted_spend_rule, $user_id ) {
		$coupon_code = wp_generate_uuid4();

		$existing_coupon = new \WC_Coupon( $coupon_code );

		if ($existing_coupon) {
			$coupon_code = wp_generate_uuid4();
		}

		$coupon = new \WC_Coupon();
		$coupon->set_code($coupon_code);
		$coupon->set_description('Piggy Spend Rule: ' . $formatted_spend_rule['title']['value']);
		$coupon->set_usage_limit(1);
		$coupon->set_individual_use(true);

		$coupon->add_meta_data('_piggy_spend_rule_coupon', 'true', true);
		$coupon->add_meta_data('_piggy_spend_rule_id', $formatted_spend_rule['id'], true);

		if( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			$user_email = $user->user_email;

			$coupon->add_meta_data('_piggy_user_id', $user_id, true);
			$coupon->set_email_restrictions( [ $user_email ] );
		}

		switch ($formatted_spend_rule['type']['value']) {
			case 'FREE_PRODUCT':
			case 'ORDER_DISCOUNT':
				$coupon->set_amount(0);

				break;

			case 'FREE_SHIPPING':
				$coupon->set_free_shipping(true);
				break;
		}

		// Check for minimum purchase amount
		if (isset($formatted_spend_rule['minimumPurchaseAmount']) &&
			is_numeric($formatted_spend_rule['minimumPurchaseAmount']['value'])) {
			$min_amount = floatval($formatted_spend_rule['minimumPurchaseAmount']['value']);
			if ($min_amount > 0) {
				$coupon->set_minimum_amount($min_amount);
			}
		}

		$coupon->save();

		return $coupon_code;
	}

	/**
	 * Query coupons by user ID.
	 *
	 * @param int $user_id The user ID.
	 * @return array The list of coupons associated with the user ID.
	 */
	public function get_coupons_by_user_id($user_id)
	{
		$args = [
			'post_type' => 'shop_coupon',
			'posts_per_page' => -1,
			'meta_query' => [
				[
					'key' => '_piggy_user_id',
					'value' => $user_id,
					'compare' => '='
				]
			]
		];

		$coupons = get_posts($args);

		$coupon_codes = [];

		foreach ($coupons as $coupon) {
			// Tie back to the spend rule
			$spend_rule_id = get_post_meta($coupon->ID, '_piggy_spend_rule_id', true);
			$spend_rule = $this->get_spend_rule_by_id($spend_rule_id);

			if( !$spend_rule ) {
				continue;
			}

			$coupon_codes[] = array(
				'code' => $coupon->post_title,
				'spend_rule' => $spend_rule,
			);
		}

		return $coupon_codes;
	}
}
