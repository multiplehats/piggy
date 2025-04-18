<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;

/**
 * Coupons class.
 *
 * @internal
 */
class Coupons extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'coupons';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'coupons';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/coupons';
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
				'permission_callback' => function ($request) {
					$user_id = $request->get_param('userId');
					return Middleware::is_valid_user(intval($user_id));
				},
				'args'                => [
					'userId' => [
						'type'              => 'integer',
						'validate_callback' => function ($param) {
							return is_numeric($param) && $param > 0;
						},
						'sanitize_callback' => 'absint',
					],
				],
			],
			'schema' => [$this->schema, 'get_public_item_schema'],
		];
	}

	/**
	 * Get user coupons
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 * @throws \RouteException If the user is not found.
	 */
	public function get_route_response(\WP_REST_Request $request)
	{
		$user_id = $request->get_param('userId');

		if (! $user_id) {
			$user_id = get_current_user_id();
		}

		if (! $user_id) {
			throw new RouteException('no_user_id', 'User ID is required', 400);
		}


		$spend_rules_coupons             = $this->spend_rules_service->get_coupons_by_user_id($user_id);
		$promotion_rules_coupons = $this->promotion_rules_service->get_coupons_by_user_id($user_id);

		$response_objects = array(
			'spend_rules_coupons' => $spend_rules_coupons,
			'promotion_rules_coupons' => $promotion_rules_coupons,
		);

		return rest_ensure_response($response_objects);
	}
}
