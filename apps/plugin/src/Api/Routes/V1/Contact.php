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
class Contact extends AbstractRoute
{
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'contact';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'contact';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path()
	{
		return '/contact';
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
		$user_id = $request->get_param('userId');

		if (! $user_id) {
			throw new RouteException('no_user_id', 'User ID is required', 400);
		}

		$contact = $this->connection->get_contact_by_wp_id($user_id);

		if (!isset($contact['uuid'])) {
			throw new RouteException('no_contact_uuid', 'Contact UUID is required', 400);
		}

		$contact_uuid = $contact['uuid'];

		$claimed_rewards = $this->connection->get_user_reward_logs($user_id);
		$tier            = $this->tier_service->get_tier_by_contact_uuid($contact_uuid);
		error_log(print_r($tier, true));
		$response = rest_ensure_response(
			array(
				'contact'        => $contact,
				'claimedRewards' => $claimed_rewards,
				'tier'           => $tier,
			)
		);

		return $response;
	}
}
