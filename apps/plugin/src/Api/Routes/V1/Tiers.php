<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Middleware;

/**
 * Contact class.
 *
 * @internal
 */
class Tiers extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'tiers';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'tiers';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/tiers';
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
			'schema' => [$this->schema, 'get_public_item_schema'],
		];
	}

	/**
	 * Get user coupons.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 *
	 * @throws RouteException If the user ID is not provided.
	 */
	public function get_route_response(\WP_REST_Request $request)
	{
		$tiers = $this->tier_service->get_tiers();

		$response = rest_ensure_response(
			array(
				'tiers' => $tiers,
			)
		);

		return $response;
	}
}
