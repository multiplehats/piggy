<?php

namespace PiggyWP\Api\Schemas\V1;

use PiggyWP\Api\Exceptions\RouteException;
use PiggyWP\Api\Schemas\V1\AbstractSchema;
use PiggyWP\Domain\Services\EarnRules as EarnRulesService;

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
				'description' => __( 'The Earn Rule ID', 'piggy' ),
				'type'        => 'integer',
				'required'    => true,
			],
			'userId' => [
				'description' => __( 'The Customer ID', 'piggy' ),
				'type'        => 'integer',
				'required'    => true,
			],
		];
	}

	/**
	 * Convert a Earn Rule post into an object suitable for the response.
	 *
	 * @param array $data The customer id and earn rule id.
	 * @return array
	 */
	public function get_item_response( $data ) {
		error_log( 'EarnRewardSchema::get_item_response' . print_r( $data, true ) );

		$post = $this->earn_rules_service->get_by_id( $data['earnRuleId'] );

		if( ! $post ) {
			throw new RouteException( 'earn-rule-not-found', 'Earn rule not found', 404 );
		}

		return $post;
	}
}
