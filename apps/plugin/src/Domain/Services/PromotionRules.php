<?php

namespace Leat\Domain\Services;

use Leat\Utils\Logger;

/**
 * Class PromotionRules
 */
class PromotionRules
{
	/**
	 * @var Logger
	 */
	private $logger;

	public function __construct()
	{
		$this->logger = new Logger();
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
			'id' => (int) $post->ID,
			'createdAt' => $post->post_date,
			'updatedAt' => $post->post_modified,
			'status' => [
				'id' => 'status',
				'label' => __('Status', 'leat'),
				'default' => 'publish',
				'value' => $post->post_status,
				'options' => [
					'publish' => ['label' => __('Active', 'leat')],
					'draft' => ['label' => __('Inactive', 'leat')],
				],
				'type' => 'select',
				'description' => __('Set the status of the rule. Inactive promotion rules will not be displayed to users.', 'leat'),
			],
			'label' => [
				'id' => 'label',
				'label' => __('Label', 'leat'),
				'default' => $this->get_default_label(),
				'value' => $this->get_post_meta_data($post->ID, '_leat_spend_rule_label'),
				'type' => 'translatable_text',
				'description' => $this->get_label_description(),
			],
			'title' => [
				'id' => 'title',
				'label' => __('Title', 'leat'),
				'default' => null,
				'value' => $post->post_title,
				'type' => 'text',
				'description' => __( 'This is not displayed to the user and is only used for internal reference. You can manage this in the Leat dashboard.', 'leat' ),
			],
			'leatPromotionUuid' => [
				'id' => 'leat_promotion_uuid',
				'label' => __('Leat Promotion UUID', 'leat'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_uuid', null),
				'type' => 'text',
				'description' => __('The UUID of the corresponding Leat promotion.', 'leat'),
			],
			'image' => [
				'id' => 'image',
				'label' => __('Image', 'leat'),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_image', null),
				'type' => 'text',
				'description' => __('The image that is displayed for the promotion rule.', 'leat'),
			],
		];

		$promotion_rule['selectedProducts'] = [
			'id' => 'selected_products',
			'label' => __('Selected products', 'leat'),
			'optional' => true,
			'default' => [],
			'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_selected_products', []),
			'type' => 'products_select',
			'description' => __('The products that are selected for the promotion rule.', 'leat'),
		];

		$promotion_rule['discountValue'] = [
			'id' => 'discount_value',
			'label' => __('Discount value', 'leat'),
			'default' => 10,
			'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_discount_value', null),
			'type' => 'number',
			'description' => __('The value of the discount.', 'leat'),
		];

		$promotion_rule['discountType'] = [
			'id' => 'discount_type',
			'label' => __('Discount type', 'leat'),
			'default' => 'percentage',
			'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_discount_type', 'percentage'),
			'type' => 'select',
			'options' => [
				'percentage' => ['label' => __('Percentage', 'leat')],
				'fixed' => ['label' => __('Fixed amount', 'leat')],
			],
			'description' => __('The type of discount.', 'leat'),
		];

		$promotion_rule['minimumPurchaseAmount'] = [
			'id' => 'minimum_purchase_amount',
			'label' => __('Minimum purchase amount', 'leat'),
			'default' => 0,
			'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_minimum_purchase_amount', 0),
			'type' => 'number',
			'description' => __('The minimum purchase amount required to redeem the promotion.', 'leat'),
		];

		// New fields
		$promotion_rule['voucherLimit'] = [
			'id' => 'voucher_limit',
			'label' => __('Voucher limit', 'leat'),
			'default' => 0,
			'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_voucher_limit', 0),
			'type' => 'number',
			'description' => __('The maximum number of vouchers that can be issued for this promotion. 0 means unlimited.', 'leat'),
		];

		$promotion_rule['limitPerContact'] = [
			'id' => 'limit_per_contact',
			'label' => __('Limit Per Contact', 'leat'),
			'default' => 0,
			'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_limit_per_contact', 1),
			'type' => 'number',
			'description' => __('The maximum number of times a single contact can use this promotion. 0 means unlimited.', 'leat'),
		];

		$promotion_rule['expirationDuration'] = [
			'id' => 'expiration_duration',
			'label' => __('Expiration Duration', 'leat'),
			'default' => 0,
			'value' => $this->get_post_meta_data($post->ID, '_leat_promotion_rule_expiration_duration', 0),
			'type' => 'number',
			'description' => __('The number of days after which the promotion expires. 0 means no expiration.', 'leat'),
		];

		return $promotion_rule;
	}

	public function delete_promotion_rule_by_leat_uuid($uuid) {
		$args = array(
			'post_type' => 'leat_promotion_rule',
			'meta_key' => '_leat_promotion_uuid',
			'meta_value' => $uuid,
			'posts_per_page' => 1,
		);

		$posts = get_posts($args);

		if (!empty($posts)) {
			wp_delete_post($posts[0]->ID);
		}
	}

	private function get_label_description()
	{
		$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
		return sprintf(__("The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'leat'), $placeholders);
	}

	private function get_default_label()
	{
		return [
			'default' => 'Unlock {{ discount }} for {{ credits }} {{ credits_currency }}'
		];
	}

	public function create_or_update_promotion_rule_from_promotion($promotion, $existing_post_id = null) {
		$post_data = array(
			'post_type' => 'leat_promotion_rule',
			'post_title' => $promotion['title'],
			'meta_input' => array(
				'_leat_promotion_uuid' => $promotion['uuid'],
				'_leat_promotion_rule_voucher_limit' => $promotion['voucherLimit'],
				'_leat_promotion_rule_limit_per_contact' => $promotion['limitPerContact'],
				'_leat_promotion_rule_expiration_duration' => $promotion['expirationDuration'],
			)
		);

		if(isset($promotion['image'])) {
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

	public function get_promotion_rule_by_leat_uuid($uuid) {
		$args = array(
			'post_type' => 'leat_promotion_rule',
			'meta_key' => '_leat_promotion_uuid',
			'meta_value' => $uuid,
			'posts_per_page' => 1,
		);

		$posts = get_posts($args);

		if (!empty($posts)) {
			return $this->get_formatted_post($posts[0]);
		}

		return null;
	}

	public function delete_promotion_rules_by_uuids($uuids_to_delete) {
		foreach ($uuids_to_delete as $post_id => $uuid) {
			wp_delete_post($post_id, true);
		}
	}

	public function handle_duplicated_promotion_rules($uuids) {
		global $wpdb;
		$table_name = $wpdb->postmeta;

		$this->logger->info("Handling duplicated promotion rules for UUIDs: " . implode(', ', $uuids));

		foreach ($uuids as $uuid) {
			$query = $wpdb->prepare(
				"SELECT post_id FROM $table_name WHERE meta_key = '_leat_promotion_uuid' AND meta_value = %s ORDER BY post_id DESC",
				$uuid
			);
			$post_ids = $wpdb->get_col($query);

			if (count($post_ids) > 1) {
				$keep_id = array_shift($post_ids);
				$this->logger->info("Keeping promotion rule with post ID: $keep_id for UUID: $uuid");

				foreach ($post_ids as $post_id) {
					$this->logger->info("Deleting duplicate promotion rule with post ID: $post_id for UUID: $uuid");
					wp_delete_post($post_id, true);
				}
			}
		}

		$this->logger->info("Finished handling duplicated promotion rules");
	}

	public function delete_promotion_rules_with_empty_uuid() {
		$args = array(
			'post_type' => 'leat_promotion_rule',
			'posts_per_page' => -1,
			'post_status' => array('publish', 'draft'),
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_leat_promotion_uuid',
					'value' => '',
					'compare' => '='
				),
				array(
					'key' => '_leat_promotion_uuid',
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

	public function get_promotion_rule_by_id($id) {
		$post = get_post($id);

		if (!$post) {
			return null;
		}

		return $this->get_formatted_post($post);
	}

	public function get_active_promotions() {
        $args = array(
            'post_type' => 'leat_promotion_rule',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $posts = get_posts($args);
        $promotion_uuids = [];

        foreach ($posts as $post) {
            $uuid = get_post_meta($post->ID, '_leat_promotion_uuid', true);
            if ($uuid) {
                $promotion_uuids[] = $uuid;
            }
        }

        return $promotion_uuids;
    }
}
