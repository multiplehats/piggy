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
					'PRODUCT_DISCOUNT' => [ 'label' => __( 'Like on Facebook', 'piggy' ) ],
				],
				'description' => __( 'The type of spend rule.', 'piggy' ),
			],
			'startsAt' => [
				'id' => 'startsAt',
				'label' => __( 'Starts at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_starts_at', null),
				'type' => 'date',
				'description' => __( 'Optional date for when the rule should start.', 'piggy' ),
			],
			'expiresAt' => [
				'id' => 'expiresAt',
				'label' => __( 'Expires at', 'piggy' ),
				'default' => null,
				'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_expires_at', null),
				'type' => 'date',
				'description' => __( 'Optional date for when the rule should expire.', 'piggy' ),
			],
			'completed' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_completed', null),
		];

		$spend_rule['label'] = [
			'id' => 'label',
			'label' => __( 'Label', 'piggy' ),
			'default' => null,
			'value' => $this->get_post_meta_data($post->ID, '_piggy_spend_rule_label', null),
			'type' => 'translatable_text',
			'description' => $this->get_label_description($type),
		];

		return $spend_rule;
	}

	private function get_label_description($type) {
		$placeholders = '';
		switch ($type) {
			case 'PRODUCT_DISCOUNT':
				$placeholders = "{{handle}}, {{credits}}, {{credits_currency}}";
				break;
		}
		return sprintf( __( "The text that's shown to the customer in the account and widgets. You can use the following placeholders: %s", 'piggy' ), $placeholders );
	}
}
