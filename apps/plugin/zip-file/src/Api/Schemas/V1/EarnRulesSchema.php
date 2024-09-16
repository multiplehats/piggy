<?php

namespace PiggyWP\Api\Schemas\V1;

use PiggyWP\Api\Schemas\V1\AbstractSchema;
use PiggyWP\Domain\Services\EarnRules as EarnRulesService;

/**
 * Settings class.
 *
 * @internal
 */
class EarnRulesSchema extends AbstractSchema {
	/**
	 * The Earn Rules service.
	 *
	 * @var EarnRulesService
	 */
	private $earn_rules_service;

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

	public function __construct() {
		$this->earn_rules_service = new EarnRulesService();
	}

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

	/**
	 * Convert a Earn Rule post into an object suitable for the response.
	 *
	 * @param \WP_Post $post Earn Rule post object.
	 * @return array
	 */
	public function get_item_response($post) {
		return $this->earn_rules_service->get_formatted_post($post);
	}
}
