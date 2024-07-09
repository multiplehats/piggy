<?php
namespace PiggyWP\Api\Schemas\V1\Admin;

use PiggyWP\Api\Schemas\V1\AbstractSchema;

/**
 * Settings class.
 *
 * @internal
 */
class SpendRulesSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'spend-rules';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'spend-rules';

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
			'creditCost' => [
				'description' => __( 'Credit cost to redeem the reward', 'piggy' ),
				'type'        => 'number',
			],
		];
	}

	private function get_post_meta_data($post_id, $key, $fallback_value = null) {
		$value = get_post_meta($post_id, $key, true);
		return empty($value) ? $fallback_value : $value;
	}

	/**
	 * Convert a Spent Rule post into an object suitable for the response.
	 *
	 * @param \WP_Post $post Spent Rule post object.
	 * @return array
	 */
	public function get_item_response($post) {
		$type = $this->get_post_meta_data($post->ID, '_piggy_spend_rule_type', null);

		$spend_rule = [
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
				'description' => __( 'Set the status of the rule. Inactive spend rules will not be displayed to users.', 'piggy' ),
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
				'default' => 'PRODUCT_DISCOUNT',
				'value' => $type,
				'type' => 'select',
				'options' => [
					'PRODUCT_DISCOUNT' => [ 'label' => __( 'Product discount', 'piggy' ) ],
					'ORDER_DISCOUNT' => [ 'label' => __( 'Order discount', 'piggy' ) ],
					'FREE_SHIPPING' => [ 'label' => __( 'Free shipping', 'piggy' ) ],
				],
				'description' => __( 'The type of spend rule.', 'piggy' ),
			],
			'startsAt' => [
				'id' => 'starts_atq	',
				'label' => __( 'Starts at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_starts_at', null),
				'type' => 'date',
				'description' => __( 'Optional date for when the rule should start.', 'piggy' ),
			],
			'expiresAt' => [
				'id' => 'expires_at',
				'label' => __( 'Expires at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_expires_at', null),
				'type' => 'date',
				'description' => __( 'Optional date for when the rule should expire.', 'piggy' ),
			],
			'completed' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_completed', null),
			'creditCost' => [
				'id' => 'credit_cost',
				'label' => __( 'Credit cost', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_credit_cost', null),
				'type' => 'number',
				'description' => __( 'The amount of credits it will cost to redeem the reward.', 'piggy' ),
			],
			'label' => [
				'id' => 'label',
				'label' => __( 'Label', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_label', null),
				'type' => 'translatable_text',
				'description' => $this->get_label_description($type),
			],
			'selectedReward' => [
				'id' => 'selected_reward',
				'label' => __( 'Selected reward', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_selected_reward', null),
				'type' => 'text',
				'description' => __( 'The reward that is selected for the spend rule.', 'piggy' ),
			],
			'description' => [
				'id' => 'description',
				'label' => __( 'Description', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_description', null),
				'type' => 'translatable_text',
				'description' => $this->get_description_placeholder($type),
			],
			'instructions' => [
				'id' => 'instructions',
				'label' => __( 'Instructions', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_instructions', null),
				'type' => 'translatable_text',
				'description' => $this->get_instructions_placeholder($type),
			],
			'fulfillment' => [
				'id' => 'fulfillment',
				'label' => __( 'Fulfillment description', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_fulfillment', null),
				'type' => 'translatable_text',
				'description' => $this->get_fulfillment_placeholder($type),
			]
		];

		if (in_array($type, ['PRODUCT_DISCOUNT', 'ORDER_DISCOUNT'])) {
			$spend_rule['discountValue'] = [
				'id' => 'discount_value',
				'label' => __( 'Discount value', 'piggy' ),
				'default' => 10,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_discount_value', null),
				'type' => 'number',
				'description' => __( 'The value of the discount.', 'piggy' ),
			];

			$spend_rule['discountType'] = [
				'id' => 'discount_type',
				'label' => __( 'Discount type', 'piggy' ),
				'default' => 'percentage',
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_discount_type', 'percentage'),
				'type' => 'select',
				'options' => [
					'percentage' => [ 'label' => __( 'Percentage', 'piggy' ) ],
					'fixed' => [ 'label' => __( 'Fixed amount', 'piggy' ) ],
				],
				'description' => __( 'The type of discount.', 'piggy' ),
			];
		}

		if (in_array($type, ['ORDER_DISCOUNT'])) {
			$spend_rule['minimumPurchaseAmount'] = [
				'id' => 'minimum_purchase_amount',
				'label' => __( 'Minimum purchase amount', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_minimum_purchase_amount', null),
				'type' => 'number',
				'description' => __( 'The minimum purchase amount required to redeem the reward.', 'piggy' ),
			];
		}

		return $spend_rule;
	}

	private function get_label_description($type) {
		$placeholders = '';
		switch ($type) {
			case 'PRODUCT_DISCOUNT':
			case 'ORDER_DISCOUNT':
			case 'FREE_SHIPPING':
				$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
				break;
		}

		/* translators: %s: a list of placeholders */
		return sprintf( __( "The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'piggy' ), $placeholders );
	}

	private function get_description_placeholder($type) {
		$placeholders = '';
		switch ($type) {
			case 'PRODUCT_DISCOUNT':
			case 'ORDER_DISCOUNT':
			case 'FREE_SHIPPING':
				$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
				break;
		}

		/* translators: %s: a list of placeholders */
		return sprintf( __( "Add a description of the reward. Available placeholders: %s", 'piggy' ), $placeholders );
	}

	private function get_instructions_placeholder($type) {
		$placeholders = '';
		switch ($type) {
			case 'PRODUCT_DISCOUNT':
			case 'ORDER_DISCOUNT':
			case 'FREE_SHIPPING':
				$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
				break;
		}

		/* translators: %s: a list of placeholders */
		return sprintf( __( "Add instructions on how to redeem the reward. Available placeholders: %s", 'piggy' ), $placeholders );
	}

	private function get_fulfillment_placeholder($type) {
		$placeholders = '';
		switch ($type) {
			case 'PRODUCT_DISCOUNT':
			case 'ORDER_DISCOUNT':
			case 'FREE_SHIPPING':
				$placeholders = "{{ credits }}, {{ credits_currency }}, {{ discount }}";
				break;
		}

		/* translators: %s: a list of placeholders */
		return sprintf( __( "Add instructions on how to fulfill will be handled. Available placeholders: %s", 'piggy' ), $placeholders );
	}
}
