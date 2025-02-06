<?php

namespace Leat\Domain\Services;

/**
 * Handles the business logic for earn rules in the loyalty program.
 *
 * This class manages earn rules, which define how users can earn credits through
 * various actions like social media follows, placing orders, or creating accounts.
 *

 */
class EarnRules
{
	/**
	 * Gets post meta data with fallback value support.
	 *

	 *
	 * @param int    $post_id        The post ID.
	 * @param string $key            The meta key to retrieve.
	 * @param mixed  $fallback_value Optional. Default value if meta is empty.
	 * @return mixed The meta value or fallback value if empty.
	 */
	private function get_post_meta_data($post_id, $key, $fallback_value = null)
	{
		$value = get_post_meta($post_id, $key, true);
		return empty($value) ? $fallback_value : $value;
	}

	/**
	 * Retrieves earn rules by their type.
	 *

	 *
	 * @param string       $type        The earn rule type to filter by.
	 * @param string|array $post_status Optional. Post status(es) to include. Default ['publish'].
	 * @return array|null Array of formatted earn rules or null if none found.
	 */
	public function get_earn_rules_by_type($type, $post_status = ['publish'])
	{
		$args = [
			'post_type'   => 'leat_earn_rule',
			'post_status' => $post_status,
			'meta_query'  => [
				[
					'key'   => '_leat_earn_rule_type',
					'value' => $type,
				],
			],
		];

		$posts = get_posts($args);

		if (empty($posts)) {
			return null;
		}

		$posts = array_map([$this, 'get_formatted_post'], $posts);

		return $posts;
	}

	/**
	 * Get an earn rule by its ID.
	 *
	 * @param int $id Earn Rule ID.
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
	 * Convert a Earn Rule post into an object suitable for a WP REST API response.
	 *
	 * @param \WP_Post $post Earn Rule post object.
	 * @return array
	 */
	public function get_formatted_post($post)
	{
		$type = $this->get_post_meta_data($post->ID, '_leat_earn_rule_type', null);

		$earn_rule = [
			'id'                 => (int) $post->ID,
			'createdAt'          => $post->post_date,
			'updatedAt'          => $post->post_modified,
			'svg'                => $this->get_svg($type),
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
				'description' => __('Set the status of the rule. Inactive earn rules will not be displayed to users.', 'leat-crm'),
			],
			'title'              => [
				'id'          => 'title',
				'label'       => __('Title', 'leat-crm'),
				'default'     => null,
				'value'       => $post->post_title,
				'type'        => 'text',
				'description' => __('This is not displayed to the user and is only used for internal reference.', 'leat-crm'),
			],
			'type'               => [
				'id'          => 'type',
				'label'       => __('Type', 'leat-crm'),
				'default'     => 'PLACE_ORDER',
				'value'       => $type,
				'type'        => 'select',
				'options'     => [
					'LIKE_ON_FACEBOOK'    => ['label' => __('Like on Facebook', 'leat-crm')],
					'FOLLOW_ON_TIKTOK'    => ['label' => __('Follow on TikTok', 'leat-crm')],
					'FOLLOW_ON_INSTAGRAM' => ['label' => __('Follow on Instagram', 'leat-crm')],
					'PLACE_ORDER'         => ['label' => __('Place an order', 'leat-crm')],
					// 'CELEBRATE_BIRTHDAY' => [ 'label' => __( 'Celebrate your birthday', 'leat-crm' ) ],
					'CREATE_ACCOUNT'      => ['label' => __('Create an account', 'leat-crm')],
				],
				'description' => __('The type of earn rule.', 'leat-crm'),
			],
			'leatTierUuids'      => $this->get_post_meta_data($post->ID, '_leat_earn_rule_leat_tier_uuids', null),
			'startsAt'           => [
				'id'          => 'startsAt',
				'label'       => __('Starts at', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_earn_rule_starts_at', null),
				'type'        => 'date',
				'description' => __('Optional date for when the rule should start.', 'leat-crm'),
			],
			'expiresAt'          => [
				'id'          => 'expiresAt',
				'label'       => __('Expires at', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_earn_rule_expires_at', null),
				'type'        => 'date',
				'description' => __('Optional date for when the rule should expire.', 'leat-crm'),
			],
			'completed'          => $this->get_post_meta_data($post->ID, '_leat_earn_rule_completed', null),
			'minimumOrderAmount' => [
				'id'          => 'minimumOrderAmount',
				'label'       => __('Minimum order amount', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_earn_rule_min_order_subtotal_cents', null),
				'type'        => 'number',
				'description' => __('The minimum order amount required to satisfy the rule', 'leat-crm'),
				'attributes'  => [
					'min'  => 0,
					'step' => 1,
				],
			],
			'credits'            => [
				'id'          => 'credits',
				'label'       => __('Credits', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_earn_rule_points', null),
				'type'        => 'number',
				'description' => __('The number of credits awarded for completing this action.', 'leat-crm'),
				'attributes'  => [
					'min'  => 0,
					'step' => 1,
				],
			],
			'socialHandle'       => [
				'id'          => 'social_handle',
				'label'       => $this->get_social_network_label($type),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_earn_rule_social_handle', null),
				'type'        => 'text',
				'description' => $this->get_social_network_description($type),
			],
		];

		$label_value = $this->get_post_meta_data($post->ID, '_leat_earn_rule_label', null);

		$earn_rule['label'] = [
			'id'          => 'label',
			'label'       => __('Label', 'leat-crm'),
			'default'     => $this->get_label_default($type),
			'value'       => isset($label_value) ? $label_value : $this->get_label_default($type),
			'type'        => 'translatable_text',
			'description' => $this->get_label_description($type),
		];

		return $earn_rule;
	}

	private function get_label_description($type)
	{
		$placeholders = '';
		switch ($type) {
			case 'PLACE_ORDER':
				$placeholders = '{{ credits_currency }}';
				break;
			case 'CREATE_ACCOUNT':
			case 'CELEBRATE_BIRTHDAY':
				$placeholders = '{{ credits }}, {{ credits_currency }}';
				break;
			case 'LIKE_ON_FACEBOOK':
			case 'FOLLOW_ON_TIKTOK':
			case 'FOLLOW_ON_INSTAGRAM':
				$placeholders = '{{ handle }}, {{ credits }}, {{ credits_currency }}';
				break;
		}

		/* translators: %s: List of placeholders that can be used in the label text (e.g. {{ credits }}, {{ credits_currency }}) */
		return sprintf(__("The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat-crm'), $placeholders);
	}

	private function get_label_default($type)
	{
		$default = '';

		switch ($type) {
			case 'LIKE_ON_FACEBOOK':
				$default = __('Follow us on Facebook and earn {{ credits }} {{ credits_currency }}', 'leat-crm');
				break;
			case 'FOLLOW_ON_TIKTOK':
				$default = __('Follow us on TikTok and earn {{ credits }} {{ credits_currency }}', 'leat-crm');
				break;
			case 'FOLLOW_ON_INSTAGRAM':
				$default = __('Follow us on Instagram and earn {{ credits }} {{ credits_currency }}', 'leat-crm');
				break;
			case 'PLACE_ORDER':
				$default = __('For every order you place, you earn {{ credits }} {{ credits_currency }}', 'leat-crm');
				break;
			case 'CELEBRATE_BIRTHDAY':
				$default = __('On your birthday, you earn {{ credits }} {{ credits_currency }}', 'leat-crm');
				break;
			case 'CREATE_ACCOUNT':
				$default = __('Create an account and earn {{ credits }} {{ credits_currency }}', 'leat-crm');
				break;
		}

		return array(
			'default' => $default,
		);
	}

	private function get_social_network_label($type)
	{
		switch ($type) {
			case 'LIKE_ON_FACEBOOK':
				return __('Facebook handle', 'leat-crm');
			case 'FOLLOW_ON_TIKTOK':
				return __('TikTok handle', 'leat-crm');
			case 'FOLLOW_ON_INSTAGRAM':
				return __('Instagram handle', 'leat-crm');
			default:
				return __('Social network handle', 'leat-crm');
		}
	}

	private function get_social_network_description($type)
	{
		switch ($type) {
			case 'LIKE_ON_FACEBOOK':
				return __('The handle of the Facebook account (without the @) that the user must like', 'leat-crm');
			case 'FOLLOW_ON_TIKTOK':
				return __('The handle of the TikTok account (without the @) that the user must follow', 'leat-crm');
			case 'FOLLOW_ON_INSTAGRAM':
				return __('The handle of the Instagram account (without the @) that the user must follow', 'leat-crm');
			default:
				return __('The handle of the social network account that the user must like or follow', 'leat-crm');
		}
	}

	private function get_svg($type)
	{
		$svg = '';

		if (! $type) {
			return $svg;
		}

		switch ($type) {
			case 'LIKE_ON_FACEBOOK':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="widget__icons-color"><path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3"></path></svg>';
				break;
			case 'FOLLOW_ON_TIKTOK':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="widget__icons-color"><path d="M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 4 2v-11.5h4.083a6.005 6.005 0 0 0 4.917 4.917z"></path></svg>';
				break;
			case 'FOLLOW_ON_INSTAGRAM':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="widget__icons-color"><path d="M4 4m0 4a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z"></path><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path><path d="M16.5 7.5l0 .01"></path></svg>';
				break;
			case 'PLACE_ORDER':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="widget__icons-color"><path d="M12.5 21h-3.926a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304h11.339a2 2 0 0 1 1.977 2.304l-.263 1.708"></path><path d="M16 19h6"></path><path d="M19 16v6"></path><path d="M9 11v-5a3 3 0 0 1 6 0v5"></path></svg>';
				break;
			case 'CELEBRATE_BIRTHDAY':
				$svg = '<svg viewBox="0 0 20 20" width="48"><path fill-rule="evenodd" d="M7.75 3.5a.75.75 0 0 0-1.5 0v.407a3.075 3.075 0 0 0-.702.252 3.75 3.75 0 0 0-1.64 1.639c-.226.444-.32.924-.365 1.47-.043.531-.043 1.187-.043 2v1.513c0 .79 0 1.428.041 1.944.042.532.131 1 .346 1.434a3.75 3.75 0 0 0 1.704 1.704c.435.215.902.304 1.434.346.517.041 1.154.041 1.944.041h.031a.75.75 0 0 0 0-1.5c-.829 0-1.406 0-1.856-.036-.442-.035-.696-.1-.89-.196a2.25 2.25 0 0 1-1.022-1.023c-.095-.193-.16-.447-.196-.889-.035-.45-.036-1.027-.036-1.856v-1.25h10v1.5a.75.75 0 0 0 1.5 0v-1.732c0-.813 0-1.469-.043-2-.045-.546-.14-1.026-.366-1.47a3.75 3.75 0 0 0-1.639-1.64 3.076 3.076 0 0 0-.702-.251v-.407a.75.75 0 0 0-1.5 0v.259c-.373-.009-.794-.009-1.268-.009h-1.964c-.474 0-.895 0-1.268.009v-.259Zm7.241 4.5a10.674 10.674 0 0 0-.03-.61c-.037-.453-.106-.714-.206-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.056-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038-.453.037-.714.107-.911.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.014.18-.023.38-.03.609h9.983Z"></path><path d="M17.28 12.72a.75.75 0 0 1 0 1.06l-3.5 3.5a.75.75 0 0 1-1.06 0l-1.75-1.75a.75.75 0 1 1 1.06-1.06l1.22 1.22 2.97-2.97a.75.75 0 0 1 1.06 0Z"></path></svg>';
				break;
			case 'CREATE_ACCOUNT':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="widget__icons-color"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path><path d="M16 19h6"></path><path d="M19 16v6"></path><path d="M6 21v-2a4 4 0 0 1 4 -4h4"></path></svg>';
				break;
			case 'ORDER':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="widget__icons-color"><path d="M12.5 21h-3.926a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304h11.339a2 2 0 0 1 1.977 2.304l-.263 1.708"></path><path d="M16 19h6"></path><path d="M19 16v6"></path><path d="M9 11v-5a3 3 0 0 1 6 0v5"></path></svg>';
				break;
		}

		return apply_filters('leat_earn_rule_svg', $svg, $type);
	}

	/**
	 * Get the applicable PLACE_ORDER earn rule for a given order amount.
	 *
	 * @param float $order_amount The order amount in the store's currency.
	 * @return array|null The applicable earn rule, or null if none found.
	 */
	public function get_applicable_place_order_rule($order_amount)
	{
		$place_order_rules = $this->get_earn_rules_by_type('PLACE_ORDER');

		if (! $place_order_rules) {
			return null;
		}

		$applicable_rule = null;
		$highest_minimum = 0;

		foreach ($place_order_rules as $rule) {
			$minimum_order_amount = $rule['minimumOrderAmount']['value'] ?? 0;

			if ($order_amount >= $minimum_order_amount && $minimum_order_amount > $highest_minimum) {
				$applicable_rule = $rule;
				$highest_minimum = $minimum_order_amount;
			}
		}

		if (! $applicable_rule) {
			$applicable_rule = $place_order_rules[0];
		}

		return $applicable_rule;
	}

	/**
	 * Check if a user has already claimed a specific earn rule.
	 *
	 * @param int $user_id The user ID.
	 * @param int $earn_rule_id The earn rule ID.
	 * @return bool
	 */
	public function has_user_claimed_rule($user_id, $earn_rule_id)
	{
		$cache_key = "leat_user_claimed_rule_{$user_id}_{$earn_rule_id}";

		$cached_result = wp_cache_get($cache_key, 'leat');
		if (false !== $cached_result) {
			return (bool) $cached_result;
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}leat_reward_logs WHERE wp_user_id = %d AND earn_rule_id = %d",
			$user_id,
			$earn_rule_id
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = (bool) $wpdb->get_var($query);

		wp_cache_set($cache_key, $result, 'leat');

		return $result;
	}

	/**
	 * Check if an earn rule is claimable only once.
	 *
	 * @param int $earn_rule_id The earn rule ID.
	 * @return bool
	 */
	public function is_rule_claimable_once($earn_rule_id)
	{
		$rule = $this->get_by_id($earn_rule_id);
		if (! $rule) {
			return false;
		}

		$once_only_types = ['LIKE_ON_FACEBOOK', 'FOLLOW_ON_TIKTOK', 'FOLLOW_ON_INSTAGRAM', 'CREATE_ACCOUNT'];
		return in_array($rule['type']['value'], $once_only_types, true);
	}
}
