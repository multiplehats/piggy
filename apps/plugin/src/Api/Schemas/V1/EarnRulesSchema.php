<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;
use Leat\Domain\Services\EarnRules as EarnRulesService;

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
			'id'                    => [
				'description' => __( 'Unique identifier for the rule', 'leat-crm' ),
				'type'        => 'integer',
			],
			'status'                => [
				'description' => __( 'Status of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'title'                 => [
				'description' => __( 'Title of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'createdAt'             => [
				'description' => __( 'Date rule was created.', 'leat-crm' ),
				'type'        => 'string',
			],
			'updatedAt'             => [
				'description' => __( 'Date rule was last updated.', 'leat-crm' ),
				'type'        => 'string',
			],
			'description'           => [
				'description' => __( 'Description of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'type'                  => [
				'description' => __( 'Type of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'leatTierUuids'         => [
				'description' => __( 'Leat tier UUIDs that rule is applicable to.', 'leat-crm' ),
				'type'        => 'array',
			],
			'startsAt'              => [
				'description' => __( 'Date rule starts.', 'leat-crm' ),
				'type'        => 'string',
			],
			'expiresAt'             => [
				'description' => __( 'Date rule expires.', 'leat-crm' ),
				'type'        => 'string',
			],
			'completed'             => [
				'description' => __( 'Whether rule has been completed.', 'leat-crm' ),
				'type'        => 'boolean',
			],
			'credits'               => [
				'description' => __( 'Credits awarded for completing the rule', 'leat-crm' ),
				'type'        => 'integer',
			],
			'socialHandle'          => [
				'description' => __( 'URL of the social network.', 'leat-crm' ),
				'type'        => 'string',
			],
			'excludedCollectionIds' => [
				'description' => __( 'Collection IDs that are excluded from the rule', 'leat-crm' ),
				'type'        => 'array',
			],
			'excludedProductIds'    => [
				'description' => __( 'Product IDs that are excluded from the rule', 'leat-crm' ),
				'type'        => 'array',
			],
			'minimumOrderAmount'    => [
				'description' => __( 'Minimum order subtotal in cents.', 'leat-crm' ),
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
	public function get_item_response( $post ) {
		return $this->earn_rules_service->get_formatted_post( $post );
	}
}
