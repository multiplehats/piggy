<?php
namespace PiggyWP\Api\Schemas\V1;

use PiggyWP\Api\Schemas\V1\AbstractSchema;
use PiggyWP\Domain\Services\SpendRules as SpendRulesService;

/**
 * Settings class.
 *
 * @internal
 */
class SpendRulesClaimSchema extends AbstractSchema {
	/**
	 * The Earn Rules service.
	 *
	 * @var SpendRulesService
	 */
	private $spend_rules_service;

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'spend-rules-claim';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'spend-rules-claim';

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

	/**
	 * Convert a Spent Rule post into an object suitable for the response.
	 *
	 * @param \WP_Post $post Spent Rule post object.
	 * @return array
	 */
	public function get_item_response($post) {
		return $this->spend_rules_service->get_formatted_post($post);
	}
}
