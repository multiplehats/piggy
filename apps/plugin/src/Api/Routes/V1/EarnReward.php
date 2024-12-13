<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Admin\Middleware;
use Leat\Domain\Services\EarnRules as EarnRulesService;

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
				'permission_callback' => '__return_true',
				'args'                => [
					'earnRuleId' => [
						'description' => __( 'The Earn Rule ID', 'leat-crm' ),
						'type'        => 'integer',
						'required'    => true,
					],
					'userId' => [
						'description' => __( 'The Customer ID', 'leat-crm' ),
						'type'        => 'integer',
						'required'    => false,
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
			'earn_rule_id' => $request->get_param( 'earnRuleId' ),
			'user_id' => $request->get_param( 'userId' ) ?? get_current_user_id(),
		);

		$earn_rules_service = new EarnRulesService();
		$post = $earn_rules_service->get_by_id( $data['earn_rule_id'] );

		if( ! $post ) {
			throw new RouteException( 'earn-rule-not-found', 'Earn rule not found', 404 );
		}

		// Check if the rule is claimable only once and if the user has already claimed it
		if ($earn_rules_service->is_rule_claimable_once($data['earn_rule_id'])) {
			if ($earn_rules_service->has_user_claimed_rule($data['user_id'], $data['earn_rule_id'])) {
				throw new RouteException( 'earn-rule-already-claimed', 'You have already claimed this.', 400 );
			}
		}

		// Get the Leat UUID for the user, if not found, create a new contact
		$contact = $this->connection->get_contact( $data['user_id'] );
		$uuid = $contact['uuid'];

		$credits = $post['credits']['value'] ?? 0;

		$this->connection->apply_credits( $uuid, $credits );

		$this->connection->add_reward_log($data['user_id'], $data['earn_rule_id'], $credits);

		$data     = $this->prepare_item_for_response( $data, $request );
		$response = $this->prepare_response_for_collection( $data );

		return rest_ensure_response( $response );
	}
}
