<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Connection;

/**
 * SpendRuleSync class.
 *
 * @internal
 */
class SpendRulesSync extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'spend-rules-sync';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'spend-rules-sync';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/spend-rules-sync';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Syncs spend rules with Leat rewards
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$connection = new Connection();
		$result     = $connection->sync_rewards_with_spend_rules();

		if ( $result ) {
			return new \WP_REST_Response( [ 'message' => 'Spend rules synced successfully with Leat rewards' ], 200 );
		} else {
			return new \WP_Error( 'sync_failed', 'Failed to sync spend rules with Leat rewards', [ 'status' => 500 ] );
		}
	}
}
