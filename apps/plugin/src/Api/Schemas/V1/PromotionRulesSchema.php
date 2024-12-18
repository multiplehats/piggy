<?php
namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;
use Leat\Domain\Services\PromotionRules as PromotionRulesService;

/**
 * Settings class.
 *
 * @internal
 */
class PromotionRulesSchema extends AbstractSchema {
	/**a
	 * The Earn Rules service.
	 *
	 * @var PromotionRulesService
	 */
	private $promotion_rules_service;

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'promotion-rules';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'promotion-rules';

	public function __construct() {
		$this->promotion_rules_service = new PromotionRulesService();
	}

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id' => [
				'description' => __( 'Unique identifier for the rule', 'leat' ),
				'type'        => 'integer',
			],
			'status' => [
				'description' => __( 'Status of the rule', 'leat' ),
				'type'        => 'string',
			],
			'title' => [
				'description' => __( 'Title of the rule', 'leat' ),
				'type'        => 'string',
			],
			'createdAt' => [
				'description' => __( 'Date rule was created.', 'leat' ),
				'type'        => 'string',
			],
			'updatedAt' => [
				'description' => __( 'Date rule was last updated.', 'leat' ),
				'type'        => 'string',
			],
			'description' => [
				'description' => __( 'Description of the rule', 'leat' ),
				'type'        => 'string',
			],
			'type' => [
				'description' => __( 'Type of the rule', 'leat' ),
				'type'        => 'string',
			],
			'startsAt' => [
				'description' => __( 'Date rule starts.', 'leat' ),
				'type'        => 'string',
			],
			'expiresAt' => [
				'description' => __( 'Date rule expires.', 'leat' ),
				'type'        => 'string',
			],
			'completed' => [
				'description' => __( 'Whether rule has been completed.', 'leat' ),
				'type'        => 'boolean',
			],
			'creditCost' => [
				'description' => __( 'Credit cost to redeem the reward', 'leat' ),
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
		return $this->promotion_rules_service->get_formatted_post( $post );
	}
}
