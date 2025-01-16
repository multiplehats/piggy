<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Schemas\V1\AbstractSchema;
use Leat\Domain\Services\EarnRules as EarnRulesService;

/**
 * Settings class.
 *
 * @internal
 */
class EarnRewardSchema extends AbstractSchema {
	/**
	 * The Earn Reward service.
	 *
	 * @var EarnRulesService
	 */
	private $earn_rules_service;

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'earn-reward';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'earn-reward';

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
			'earnRuleId' => [
				'description' => __( 'The Earn Rule ID', 'leat-crm' ),
				'type'        => 'integer',
				'required'    => true,
			],
			'userId'     => [
				'description' => __( 'The Customer ID', 'leat-crm' ),
				'type'        => 'integer',
				'required'    => false,
			],
		];
	}

	/**
	 * Convert a Earn Rule post into an object suitable for the response.
	 *
	 * @param array $data The customer id and earn rule id.
	 * @return array
	 * @throws RouteException If the earn rule is not found.
	 */
	public function get_item_response( $data ) {
		$post = $this->earn_rules_service->get_by_id( $data['earn_rule_id'] );

		if ( empty( $post ) || is_wp_error( $post ) ) {
			throw new RouteException( 'earn-rule-not-found', 'Earn rule not found', 404 );
		}

		return $post;
	}
}
