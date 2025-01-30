<?php

namespace Leat\Domain\Services;

use Leat\Utils\Coupons;
use Leat\Utils\Logger;

/**
 * Class PromotionRules
 */
class PromotionRules
{
	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	public function __construct()
	{
		$this->logger     = new Logger();
	}

	private function get_post_meta_data($post_id, $key, $fallback_value = null)
	{
		$value = get_post_meta($post_id, $key, true);

		return empty($value) ? $fallback_value : $value;
	}

	/**
	 * Get a promotion rule by its ID.
	 *
	 * @param int $id Promotion Rule ID.
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
	 * Convert a Promotion Rule post into an object suitable for a WP REST API response.
	 *
	 * @param \WP_Post $post Promotion Rule post object.
	 * @return array
	 */
	public function get_formatted_post($post)
	{
		$promotion_rule = [
			'id'                => (int) $post->ID,
			'createdAt'         => $post->post_date,
			'updatedAt'         => $post->post_modified,
			'status'            => [
				'id'          => 'status',
				'label'       => __('Status', 'leat-crm'),
				'default'     => 'publish',
				'value'       => $post->post_status,
				'options'     => [
					'publish' => ['label' => __('Active', 'leat-crm')],
					'draft'   => ['label' => __('Inactive', 'leat-crm')],
				],
				'type'        => 'select',
				'description' => __('Set the status of the rule. Inactive promotion rules will not be displayed to users.', 'leat-crm'),
			],
			'label'             => [
				'id'          => 'label',
				'label'       => __('Label', 'leat-crm'),
				'default'     => $this->get_default_label(),
				'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_label'),
				'type'        => 'translatable_text',
				'description' => $this->get_label_description(),
			],
			'title'             => [
				'id'          => 'title',
				'label'       => __('Title', 'leat-crm'),
				'default'     => null,
				'value'       => $post->post_title,
				'type'        => 'text',
				'description' => __('This is not displayed to the user and is only used for internal reference. You can manage this in the Leat dashboard.', 'leat-crm'),
			],
			'leatPromotionUuid' => [
				'id'          => 'leat_promotion_uuid',
				'label'       => __('Leat Promotion UUID', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_uuid', null),
				'type'        => 'text',
				'description' => __('The UUID of the corresponding Leat promotion.', 'leat-crm'),
			],
			'image'             => [
				'id'          => 'image',
				'label'       => __('Image', 'leat-crm'),
				'default'     => null,
				'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_image', null),
				'type'        => 'text',
				'description' => __('The image that is displayed for the promotion rule.', 'leat-crm'),
			],
		];

		$promotion_rule['selectedProducts'] = [
			'id'          => 'selected_products',
			'label'       => __('Selected products', 'leat-crm'),
			'optional'    => true,
			'default'     => [],
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_selected_products', []),
			'type'        => 'products_select',
			'description' => __('The products that are selected for the promotion rule.', 'leat-crm'),
		];

		$promotion_rule['discountValue'] = [
			'id'          => 'discount_value',
			'label'       => __('Discount value', 'leat-crm'),
			'default'     => 10,
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_discount_value', null),
			'type'        => 'number',
			'description' => __('The value of the discount.', 'leat-crm'),
		];

		$promotion_rule['discountType'] = [
			'id'          => 'discount_type',
			'label'       => __('Discount type', 'leat-crm'),
			'default'     => 'percentage',
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_discount_type', 'percentage'),
			'type'        => 'select',
			'options'     => [
				'percentage' => ['label' => __('Percentage', 'leat-crm')],
				'fixed'      => ['label' => __('Fixed amount', 'leat-crm')],
			],
			'description' => __('The type of discount.', 'leat-crm'),
		];

		$promotion_rule['minimumPurchaseAmount'] = [
			'id'          => 'minimum_purchase_amount',
			'label'       => __('Minimum purchase amount', 'leat-crm'),
			'default'     => 0,
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_minimum_purchase_amount', 0),
			'type'        => 'number',
			'description' => __('The minimum purchase amount required to redeem the promotion.', 'leat-crm'),
		];

		$promotion_rule['voucherLimit'] = [
			'id'          => 'voucher_limit',
			'label'       => __('Voucher limit', 'leat-crm'),
			'default'     => 0,
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_voucher_limit', 0),
			'type'        => 'number',
			'description' => __('The maximum number of vouchers that can be issued for this promotion. 0 means unlimited.', 'leat-crm'),
		];

		$promotion_rule['individualUse'] = [
			'id'          => 'individual_use',
			'label'       => __('Individual Use', 'leat-crm'),
			'default'     => 'off',
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_individual_use', 'off'),
			'type'        => 'switch',
			'description' => __('Check this box if the coupon cannot be used in conjunction with other coupons.', 'leat-crm'),
		];

		$promotion_rule['limitPerContact'] = [
			'id'          => 'limit_per_contact',
			'label'       => __('Limit Per Contact', 'leat-crm'),
			'default'     => 0,
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_limit_per_contact', 1),
			'type'        => 'number',
			'description' => __('The maximum number of times a single contact can use this promotion. 0 means unlimited.', 'leat-crm'),
		];

		$promotion_rule['expirationDuration'] = [
			'id'          => 'expiration_duration',
			'label'       => __('Expiration Duration', 'leat-crm'),
			'default'     => 0,
			'value'       => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_expiration_duration', 0),
			'type'        => 'number',
			'description' => __('The number of days after which the promotion expires. 0 means no expiration.', 'leat-crm'),
		];

		return $promotion_rule;
	}

	public function delete_promotion_rule_by_leat_uuid($uuid)
	{
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'     => '_leat_promotion_uuid',
					'value'   => $uuid,
					'compare' => '=',
				),
			),
		);

		$posts = get_posts($args);

		if (! empty($posts)) {
			wp_delete_post($posts[0]->ID);
		}
	}

	private function get_label_description()
	{
		$placeholders = '{{ credits }}, {{ credits_currency }}, {{ discount }}';

		return sprintf(
			/* translators: %s: List of available placeholders that can be used in the promotion label text */
			__("The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat-crm'),
			$placeholders
		);
	}

	private function get_default_label()
	{
		return [
			'default' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}',
		];
	}

	/**
	 * Create or update a promotion rule from a promotion.
	 *
	 * @param array $promotion The promotion data.
	 * @param int|null $existing_post_id The existing post ID.
	 * @return void
	 */
	public function create_or_update_promotion_rule_from_promotion($promotion, $existing_post_id = null): void
	{
		$post_data = array(
			'post_type'  => 'leat_promotion_rule',
			'post_title' => $promotion['title'],
			'meta_input' => array(
				'_leat_promotion_uuid'                     => $promotion['uuid'],
				'_leat_promotion_rule_voucher_limit'       => $promotion['voucher_limit'],
				'_leat_promotion_rule_limit_per_contact'   => $promotion['limit_per_contact'],
				'_leat_promotion_rule_redemptions_per_voucher' => $promotion['redemptions_per_voucher'],
				'_leat_promotion_rule_expiration_duration' => $promotion['expiration_duration'],
			),
		);

		if (isset($promotion['image'])) {
			$post_data['meta_input']['_leat_promotion_rule_image'] = $promotion['image'];
		}

		if ($existing_post_id) {
			$post_data['ID'] = $existing_post_id;
			wp_update_post($post_data);
		} else {
			// New rules are always draft by default.
			$post_data['post_status'] = 'draft';

			wp_insert_post($post_data);
		}
	}

	public function get_promotion_rule_by_leat_uuid($uuid)
	{
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'     => '_leat_promotion_uuid',
					'value'   => $uuid,
					'compare' => '=',
				),
			),
		);

		$posts = get_posts($args);

		if (! empty($posts)) {
			return $this->get_formatted_post($posts[0]);
		}

		return null;
	}

	public function delete_promotion_rules_by_uuids($uuids_to_delete)
	{
		foreach ($uuids_to_delete as $post_id => $uuid) {
			wp_delete_post($post_id, true);
		}
	}

	public function handle_duplicated_promotion_rules($uuids)
	{
		$this->logger->info('Handling duplicated promotion rules for UUIDs: ' . implode(', ', $uuids));

		foreach ($uuids as $uuid) {
			$args = array(
				'post_type'      => 'leat_promotion_rule',
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_leat_promotion_uuid',
						'value'   => $uuid,
						'compare' => '=',
					),
				),
			);

			$post_ids = get_posts($args);

			if (count($post_ids) > 1) {
				$keep_id = array_shift($post_ids);
				$this->logger->info("Keeping promotion rule with post ID: $keep_id for UUID: $uuid");

				foreach ($post_ids as $post_id) {
					$this->logger->info("Deleting duplicate promotion rule with post ID: $post_id for UUID: $uuid");
					wp_delete_post($post_id, true);
				}
			}
		}

		$this->logger->info('Finished handling duplicated promotion rules');
	}

	public function delete_promotion_rules_with_empty_uuid()
	{
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'posts_per_page' => -1,
			'post_status'    => array('publish', 'draft'),
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_leat_promotion_uuid',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => '_leat_promotion_uuid',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$posts = get_posts($args);

		foreach ($posts as $post) {
			wp_delete_post($post->ID, true);
		}

		return count($posts);
	}

	public function get_promotion_rule_by_id($id)
	{
		$post = get_post($id);

		if (! $post) {
			return null;
		}

		return $this->get_formatted_post($post);
	}

	/**
	 * Get all active promotion rules.
	 *
	 * @return array
	 */
	public function get_active_promotion_rules()
	{
		$args = array(
			'post_type'      => 'leat_promotion_rule',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		$formatted_posts = [];
		$posts           = get_posts($args);

		foreach ($posts as $post) {
			$uuid = get_post_meta($post->ID, '_leat_promotion_uuid', true);

			if ($uuid) {
				$formatted_posts[] = $this->get_formatted_post($post);
			}
		}

		return $formatted_posts;
	}

	public function get_discount_type($value)
	{
		if ('percentage' === $value) {
			return 'percent';
		} elseif ('fixed' === $value) {
			return 'fixed_product';
		}

		return null;
	}

	/**
	 * Query coupons by user ID.
	 *
	 * @param int $user_id The user ID.
	 * @return array The list of valid, usable coupons associated with the user ID.
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

			$post_id = get_post_meta($coupon->get_id(), '_leat_promotion_rule_id', true);

			if (! $post_id) {
				continue;
			}

			$rule = $this->get_promotion_rule_by_id($post_id);

			if (! $rule) {
				continue;
			}

			$coupon_codes[] = array(
				'type' => 'promotion_rule',
				'code' => $coupon->get_code(),
				'rule' => $rule,
			);
		}

		return $coupon_codes;
	}
}
