<?php

namespace PiggyWP\Api\Routes\V1;

use PiggyWP\Api\Exceptions\RouteException;
use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Api\Routes\V1\Admin\Middleware;
use PiggyWP\Domain\Services\EarnRules as EarnRulesService;

/**
 * Shops class.
 *
 * @internal
 */
class EarnReward extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'earn-reward';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'earn-reward';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/earn-reward';
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
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [
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
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Earns the reward for a customer
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$data = array(
			'earnRuleId' => $request->get_param( 'earnRuleId' ),
			'userId' => $request->get_param( 'userId' ),
		);

		$earn_rules_service = new EarnRulesService();
		$post = $earn_rules_service->get_by_id( $data['earnRuleId'] );

		if( ! $post ) {
			throw new RouteException( 'earn-rule-not-found', 'Earn rule not found', 404 );
		}

		$piggy_uuid = $this->connection->get_contact_uuid_by_wp_id( $data['userId'] );

		$credits = $post['credits']['value'] ?? 0;

		error_log( 'Applying ' . $credits . ' credits to user ' . $data['userId'] );

		$this->connection->apply_credits( $piggy_uuid, $credits );

		$data     = $this->prepare_item_for_response( $data, $request );
		$response = $this->prepare_response_for_collection( $data );

		return rest_ensure_response( $response );
	}
}
