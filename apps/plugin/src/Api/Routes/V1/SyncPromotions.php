<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Connection;
use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\Middleware;

/**
 * PromotionRuleSync class.
 *
 * @internal
 */
class SyncPromotions extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'sync-promotions';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'sync-promotions';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/sync-promotions';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args()
	{
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => [Middleware::class, 'is_authorized'],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => [Middleware::class, 'is_authorized'],
			],
			'schema'      => [$this->schema, 'get_public_item_schema'],
			'allow_batch' => ['v1' => true],
		];
	}

	/**
	 * Get the response for the route.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_response(\WP_REST_Request $request)
	{
		$process_status = $this->sync_promotions->get_process_status();

		return rest_ensure_response($process_status);
	}

	/**
	 * Syncs promotion rules with Leat promotions
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$has_started = $this->sync_promotions->start_sync();

		if (!$has_started) {
			throw new RouteException('sync-promotions', 'Sync process is already running. Skipping new sync request.', 400);
		}

		return rest_ensure_response(
			[
				'success' => true,
			]
		);
	}
}
