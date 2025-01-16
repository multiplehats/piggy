<?php
namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;
use Leat\Domain\Services\SpendRules as SpendRulesService;

/**
 * Settings class.
 *
 * @internal
 */
class SpendRulesSchema extends AbstractSchema {
	/**
	 * The Spend Rules service.
	 *
	 * @var SpendRulesService
	 */
	private $spend_rules_service;

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

	public function __construct() {
		$this->spend_rules_service = new SpendRulesService();
	}

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id'          => [
				'description' => __( 'Unique identifier for the rule', 'leat-crm' ),
				'type'        => 'integer',
			],
			'status'      => [
				'description' => __( 'Status of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'title'       => [
				'description' => __( 'Title of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'createdAt'   => [
				'description' => __( 'Date rule was created.', 'leat-crm' ),
				'type'        => 'string',
			],
			'updatedAt'   => [
				'description' => __( 'Date rule was last updated.', 'leat-crm' ),
				'type'        => 'string',
			],
			'description' => [
				'description' => __( 'Description of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'type'        => [
				'description' => __( 'Type of the rule', 'leat-crm' ),
				'type'        => 'string',
			],
			'startsAt'    => [
				'description' => __( 'Date rule starts.', 'leat-crm' ),
				'type'        => 'string',
			],
			'expiresAt'   => [
				'description' => __( 'Date rule expires.', 'leat-crm' ),
				'type'        => 'string',
			],
			'completed'   => [
				'description' => __( 'Whether rule has been completed.', 'leat-crm' ),
				'type'        => 'boolean',
			],
			'creditCost'  => [
				'description' => __( 'Credit cost to redeem the reward', 'leat-crm' ),
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
	public function get_item_response( $post ) {
		return $this->spend_rules_service->get_formatted_post( $post );
	}
}
