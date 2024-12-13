<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Admin\Middleware;
use Leat\Api\Connection;
use Leat\Api\Exceptions\RouteException;
use Leat\Domain\Services\SpendRules;

/**
 * SpendRuleSync class.
 *
 * @internal
 */
class SpendRulesClaim extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'spend-rules-claim';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'spend-rules-claim';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/spend-rules-claim';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'type' => 'integer',
						'required' => true,
					],
					'user_id' => [
						'type' => 'integer',
						'required' => false,
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Claims a spend rule.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$spend_rules_service = new SpendRules();
		$connection = new Connection();
		$id = $request->get_param( 'id' );
		$user_id = $request->get_param( 'userId' );

		if ( ! $id ) {
			throw new RouteException( 'spend-rules-claim', 'Spend rule ID is required', 400 );
		}

		if ( ! $user_id ) {
			throw new RouteException( 'spend-rules-claim', 'User ID is required', 400 );
		}

		$rule = $spend_rules_service->get_spend_rule_by_id( $id );

		if ( ! $rule ) {
			throw new RouteException( 'spend-rules-claim', 'Spend rule not found', 404 );
		}

		if ( $rule['status']['value'] === 'draft' ) {
			throw new RouteException( 'spend-rules-claim', 'Spend rule is a draft', 400 );
		}

		// Get the contact UUID for the user
		$contact = $connection->get_contact( $user_id );
		$uuid = $contact['uuid'];

		if ( ! $uuid ) {
			throw new RouteException( 'spend-rules-claim', 'User not found in Leat', 404 );
		}

		$reward_uuid = $rule['leatRewardUuid']['value'];

		if ( ! $reward_uuid ) {
			throw new RouteException( 'spend-rules-claim', 'Reward UUID not found for this spend rule', 404 );
		}

		// Create a Reward Reception
		try {
			$reception = $connection->create_reward_reception( $uuid, $reward_uuid );
		} catch (\Throwable $th) {
			// If the message sdtars with "You have insufficient credits" we return a 400
			if ( strpos( $th->getMessage(), 'You have insufficient credits' ) !== false ) {
				throw new RouteException( 'spend-rules-claim', 'Insufficient credits', 400);
			}

			throw new RouteException( 'spend-rules-claim', 'Failed to create Reward Reception', 500 );
		}

		if ( ! $reception ) {
			throw new RouteException( 'spend-rules-claim', 'Failed to create Reward Reception', 500 );
		}

		// If all is good, we create a coupon
		$coupon = $spend_rules_service->create_coupon_for_spend_rule( $rule, $user_id ); // Pass user ID

		if ( ! $coupon ) {
			throw new RouteException( 'spend-rules-claim', 'Failed to create coupon', 500 );
		}

		return [
			'coupon' => $coupon,
			'reward_reception' => $reception,
		];
	}
}
