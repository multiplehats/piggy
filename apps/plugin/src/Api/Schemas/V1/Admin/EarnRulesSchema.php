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
				'description' => __( 'Unique identifier for the earn rule.', 'piggy' ),
				'type'        => 'integer',
			],
			'status' => [
				'description' => __( 'Status of the earn rule.', 'piggy' ),
				'type'        => 'string',
			],
			'title' => [
				'description' => __( 'Title of the earn rule.', 'piggy' ),
				'type'        => 'string',
			],
			'createdAt' => [
				'description' => __( 'Date the earn rule was created.', 'piggy' ),
				'type'        => 'string',
			],
			'updatedAt' => [
				'description' => __( 'Date the earn rule was last updated.', 'piggy' ),
				'type'        => 'string',
			],
			'description' => [
				'description' => __( 'Description of the earn rule.', 'piggy' ),
				'type'        => 'string',
			],
			'type' => [
				'description' => __( 'Type of the earn rule.', 'piggy' ),
				'type'        => 'string',
			],
			'piggyTierUuids' => [
				'description' => __( 'Piggy tier UUIDs that the earn rule is applicable to.', 'piggy' ),
				'type'        => 'array',
			],
			'startsAt' => [
				'description' => __( 'Date the earn rule starts.', 'piggy' ),
				'type'        => 'string',
			],
			'expiresAt' => [
				'description' => __( 'Date the earn rule expires.', 'piggy' ),
				'type'        => 'string',
			],
			'completed' => [
				'description' => __( 'Whether the earn rule has been completed.', 'piggy' ),
				'type'        => 'boolean',
			],
			'points' => [
				'description' => __( 'Points awarded for completing the earn rule.', 'piggy' ),
				'type'        => 'integer',
			],
			'socialNetworkUrl' => [
				'description' => __( 'URL of the social network.', 'piggy' ),
				'type'        => 'string',
			],
			'socialMessage' => [
				'description' => __( 'Message to be shared on the social network.', 'piggy' ),
				'type'        => 'string',
			],
			'excludedCollectionIds' => [
				'description' => __( 'Collection IDs that are excluded from the earn rule.', 'piggy' ),
				'type'        => 'array',
			],
			'excludedProductIds' => [
				'description' => __( 'Product IDs that are excluded from the earn rule.', 'piggy' ),
				'type'        => 'array',
			],
			'minOrderSubtotalCents' => [
				'description' => __( 'Minimum order subtotal in cents.', 'piggy' ),
				'type'        => 'integer',
			],
			'points' => [
				'description' => __( 'Points awarded for completing the earn rule.', 'piggy' ),
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
	 * @param \WP_Post $post Earn RUle post object.
	 * @return array
	 */
	public function get_item_response( $post ) {
		$earn_rule = [
			'id'  => (int) $post->ID,
			'createdAt' => $post->post_date,
			'updatedAt' => $post->post_modified,
			'status' => array(
				'id' => 'status',
				'label' => __( 'Status', 'piggy' ),
				'default' => 'publish',
				'value' => $post->post_status,
				'options' => array(
					'publish' => array(
						'label' => __( 'Active', 'piggy' ),
					),
					'draft' => array(
						'label' => __( 'Inactive', 'piggy' ),
					),
				),
				'type' => 'select',
				'tooltip' => null,
				'placeholder' => null,
				'description' => __( 'Set the status of the earn rule. Inactive earn rules will not be displayed to users.', 'piggy' ),
			),
			'title' => array(
				'id' => 'title',
				'label' => __( 'Title', 'piggy' ),
				'default' => null,
				'value' => $post->post_title,
				'type' => 'text',
				'placeholder' => null,
				'description' => __( 'This is not displayed to the user and is only used for internal reference.', 'piggy' ),
			),
			'label' => array(
				'id' => 'label',
				'label' => __( 'Label', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data( $post->ID, '_piggy_earn_rule_label', null ),
				'type' => 'translatable_text',
				'placeholder' => null,
				'description' => __( 'The label of the earn rule, shown in the account and widgets.', 'piggy' ),
			),
			'type' => array(
				'id' => 'type',
				'label' => __( 'Type', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data( $post->ID, '_piggy_earn_rule_type', null ),
				'type' => 'select',
				'options' => array(
					'LIKE_ON_FACEBOOK' => array(
						'label' => __( 'Like on Facebook', 'piggy' ),
					),
					'FOLLOW_ON_TIKTOK' => array(
						'label' => __( 'Follow on TikTok', 'piggy' ),
					),
					'FOLLOW_ON_INSTAGRAM' => array(
						'label' => __( 'Follow on Instagram', 'piggy' ),
					),
					'PLACE_ORDER' => array(
						'label' => __( 'Place an order', 'piggy' ),
					),
					'CELEBRATE_BIRTHDAY' => array(
						'label' => __( 'Celebrate your birthday', 'piggy' ),
					),
					'CREATE_ACCOUNT' => array(
						'label' => __( 'Create an account', 'piggy' ),
					),
				),
				'tooltip' => null,
				'placeholder' => null,
				'description' => __( 'The type of earn rule.', 'piggy' ),
			),
			'piggyTierUuids' => $this->get_post_meta_data( $post->ID, '_piggy_earn_rule_piggy_tier_uuids', null ),
			'startsAt' => array(
				'id' => 'startsAt',
				'label' => __( 'Starts at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data( $post->ID, '_piggy_earn_rule_starts_at', null ),
				'type' => 'date',
				'placeholder' => null,
				'description' => __( 'Optional dae for when the rule starts.', 'piggy' ),
			),
			'expiresAt' => array(
				'id' => 'expiresAt',
				'label' => __( 'Expires at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data( $post->ID, '_piggy_earn_rule_expires_at', null ),
				'type' => 'date',
				'placeholder' => null,
				'description' => __( 'Optional date for when the rule expires.', 'piggy' ),
			),
			'completed' => $this->get_post_meta_data( $post->ID, '_piggy_earn_rule_completed', null ),
		];

		switch ($earn_rule['type']) {
			case 'LIKE_ON_FACEBOOK':
			case 'FOLLOW_ON_TIKTOK':
			case 'FOLLOW_ON_INSTAGRAM':
				$earn_rule['points'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_points', null);
				$earn_rule['socialNetworkUrl'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_social_network_url', null);
				$earn_rule['socialMessage'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_social_message', null);
				break;
			case 'PLACE_ORDER':
				$earn_rule['excludedCollectionIds'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_excluded_collection_ids', array());
				$earn_rule['excludedProductIds'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_excluded_product_ids', array());
				$earn_rule['minOrderSubtotalCents'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_min_order_subtotal_cents', null);
				break;
			case 'CELEBRATE_BIRTHDAY':
				$earn_rule['points'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_points', null);
				break;
			case 'CREATE_ACCOUNT':
				$earn_rule['points'] =  $this->get_post_meta_data(get_the_ID(), '_piggy_earn_rule_points', null);
				break;
		}

		return $earn_rule;
	}
}
