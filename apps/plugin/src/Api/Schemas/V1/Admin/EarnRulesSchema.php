<?php
namespace PiggyWP\Api\Schemas\V1\Admin;

use PiggyWP\Api\Schemas\V1\AbstractSchema;

/**
 * Settings class.
 *
 * @internal
 */
class EarnRulesSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'earn-rules';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'earn-rules';

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id' => [
				'description' => __( 'Unique identifier for the rule', 'piggy' ),
				'type'        => 'integer',
			],
			'status' => [
				'description' => __( 'Status of the rule', 'piggy' ),
				'type'        => 'string',
			],
			'title' => [
				'description' => __( 'Title of the rule', 'piggy' ),
				'type'        => 'string',
			],
			'createdAt' => [
				'description' => __( 'Date rule was created.', 'piggy' ),
				'type'        => 'string',
			],
			'updatedAt' => [
				'description' => __( 'Date rule was last updated.', 'piggy' ),
				'type'        => 'string',
			],
			'description' => [
				'description' => __( 'Description of the rule', 'piggy' ),
				'type'        => 'string',
			],
			'type' => [
				'description' => __( 'Type of the rule', 'piggy' ),
				'type'        => 'string',
			],
			'piggyTierUuids' => [
				'description' => __( 'Piggy tier UUIDs that rule is applicable to.', 'piggy' ),
				'type'        => 'array',
			],
			'startsAt' => [
				'description' => __( 'Date rule starts.', 'piggy' ),
				'type'        => 'string',
			],
			'expiresAt' => [
				'description' => __( 'Date rule expires.', 'piggy' ),
				'type'        => 'string',
			],
			'completed' => [
				'description' => __( 'Whether rule has been completed.', 'piggy' ),
				'type'        => 'boolean',
			],
			'credits' => [
				'description' => __( 'Credits awarded for completing the rule', 'piggy' ),
				'type'        => 'integer',
			],
			'socialHandle' => [
				'description' => __( 'URL of the social network.', 'piggy' ),
				'type'        => 'string',
			],
			'excludedCollectionIds' => [
				'description' => __( 'Collection IDs that are excluded from the rule', 'piggy' ),
				'type'        => 'array',
			],
			'excludedProductIds' => [
				'description' => __( 'Product IDs that are excluded from the rule', 'piggy' ),
				'type'        => 'array',
			],
			'minimumOrderAmount' => [
				'description' => __( 'Minimum order subtotal in cents.', 'piggy' ),
				'type'        => 'integer',
			],
		];
	}

	private function get_post_meta_data($post_id, $key, $fallback_value = null) {
		$value = get_post_meta($post_id, $key, true);
		return empty($value) ? $fallback_value : $value;
	}

	/**
	 * Convert a Earn Rule post into an object suitable for the response.
	 *
	 * @param \WP_Post $post Earn Rule post object.
	 * @return array
	 */
	public function get_item_response($post) {
		$type = $this->get_post_meta_data($post->ID, '_piggy_earn_rule_type', null);

		$earn_rule = [
			'id' => (int) $post->ID,
			'createdAt' => $post->post_date,
			'updatedAt' => $post->post_modified,
			'status' => [
				'id' => 'status',
				'label' => __( 'Status', 'piggy' ),
				'default' => 'publish',
				'value' => $post->post_status,
				'options' => [
					'publish' => [ 'label' => __( 'Active', 'piggy' ) ],
					'draft' => [ 'label' => __( 'Inactive', 'piggy' ) ],
				],
				'type' => 'select',
				'description' => __( 'Set the status of the rule. Inactive earn rules will not be displayed to users.', 'piggy' ),
			],
			'title' => [
				'id' => 'title',
				'label' => __( 'Title', 'piggy' ),
				'default' => null,
				'value' => $post->post_title,
				'type' => 'text',
				'description' => __( 'This is not displayed to the user and is only used for internal reference.', 'piggy' ),
			],
			'type' => [
				'id' => 'type',
				'label' => __( 'Type', 'piggy' ),
				'default' => 'PLACE_ORDER',
				'value' => $type,
				'type' => 'select',
				'options' => [
					'LIKE_ON_FACEBOOK' => [ 'label' => __( 'Like on Facebook', 'piggy' ) ],
					'FOLLOW_ON_TIKTOK' => [ 'label' => __( 'Follow on TikTok', 'piggy' ) ],
					'FOLLOW_ON_INSTAGRAM' => [ 'label' => __( 'Follow on Instagram', 'piggy' ) ],
					'PLACE_ORDER' => [ 'label' => __( 'Place an order', 'piggy' ) ],
					'CELEBRATE_BIRTHDAY' => [ 'label' => __( 'Celebrate your birthday', 'piggy' ) ],
					'CREATE_ACCOUNT' => [ 'label' => __( 'Create an account', 'piggy' ) ],
				],
				'description' => __( 'The type of earn rule.', 'piggy' ),
			],
			'piggyTierUuids' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_piggy_tier_uuids', null),
			'startsAt' => [
				'id' => 'startsAt',
				'label' => __( 'Starts at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_starts_at', null),
				'type' => 'date',
				'description' => __( 'Optional date for when the rule should start.', 'piggy' ),
			],
			'expiresAt' => [
				'id' => 'expiresAt',
				'label' => __( 'Expires at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_expires_at', null),
				'type' => 'date',
				'description' => __( 'Optional date for when the rule should expire.', 'piggy' ),
			],
			'completed' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_completed', null),
			'minimumOrderAmount' => [
				'id' => 'minimumOrderAmount',
				'label' => __( 'Minimum order amount', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_min_order_subtotal_cents', null),
				'type' => 'number',
				'description' => __( 'The minimum order amount required to satisfy the rule', 'piggy' ),
				'attributes' => [
					'min' => 0,
					'step' => 1,
				],
			],
			'credits' => [
				'id' => 'credits',
				'label' => __( 'Credits', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_points', null),
				'type' => 'number',
				'description' => __( 'The number of credits awarded for completing this action.', 'piggy' ),
				'attributes' => [
					'min' => 0,
					'step' => 1,
				],
			],
			'label' => [
				'id' => 'label',
				'label' => __( 'Label', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_label', null),
				'type' => 'translatable_text',
				'description' => $this->get_label_description($type),
			],
			'socialHandle' => [
				'id' => 'social_handle',
				'label' => $this->get_social_network_label($type),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_earn_rule_social_handle', null),
				'type' => 'text',
				'description' => $this->get_social_network_description($type),
			]
		];

		// if (in_array($type, ['LIKE_ON_FACEBOOK', 'FOLLOW_ON_TIKTOK', 'PLACE_ORDER'])) {
			// $earn_rule['excludedCollectionIds'] = $this->get_post_meta_data($post->ID, '_piggy_earn_rule_excluded_collection_ids', []);
			// $earn_rule['excludedProductIds'] = $this->get_post_meta_data($post->ID, '_piggy_earn_rule_excluded_product_ids', []);
		// }

		return $earn_rule;
	}

	private function get_label_description($type) {
		$placeholders = '';
		switch ($type) {
			case 'PLACE_ORDER':
				$placeholders = "{{ credits_currency }}";
				break;
			case 'CREATE_ACCOUNT':
			case 'CELEBRATE_BIRTHDAY':
				$placeholders = "{{ credits }}, {{ credits_currency }}";
				break;
			case 'LIKE_ON_FACEBOOK':
			case 'FOLLOW_ON_TIKTOK':
			case 'FOLLOW_ON_INSTAGRAM':
				$placeholders = "{{ handle }}, {{ credits }}, {{ credits_currency }}";
				break;
		}

		/* translators: %s: a list of placeholders */
		return sprintf( __( "The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'piggy' ), $placeholders );
	}

	private function get_social_network_label($type) {
		switch ($type) {
			case 'LIKE_ON_FACEBOOK':
				return __( 'Facebook handle', 'piggy' );
			case 'FOLLOW_ON_TIKTOK':
				return __( 'TikTok handle', 'piggy' );
			case 'FOLLOW_ON_INSTAGRAM':
				return __( 'Instagram handle', 'piggy' );
			default:
				return __( 'Social network handle', 'piggy' );
		}
	}

	private function get_social_network_description($type) {
		switch ($type) {
			case 'LIKE_ON_FACEBOOK':
				return __( 'The handle of the Facebook account (without the @) that the user must like', 'piggy' );
			case 'FOLLOW_ON_TIKTOK':
				return __( 'The handle of the TikTok account (without the @) that the user must follow', 'piggy' );
			case 'FOLLOW_ON_INSTAGRAM':
				return __( 'The handle of the Instagram account (without the @) that the user must follow', 'piggy' );
			default:
				return __( 'The handle of the social network account that the user must like or follow', 'piggy' );
		}
	}
}
