<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\Middleware;

class JoinProgram extends AbstractRoute
{
	const IDENTIFIER  = 'join-program';
	const SCHEMA_TYPE = 'join-program';

	public function get_path()
	{
		return '/join-program';
	}

	public function get_args()
	{
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'get_response'],
				'permission_callback' => function ($request) {
					$user_id = $request->get_param('userId');
					return Middleware::is_valid_user(intval($user_id));
				},
				'args'                => [
					'userId' => [
						'required' => true,
						'type'     => 'integer',
					],
				],
			],
			'schema' => [$this->schema, 'get_public_item_schema'],
		];
	}

	/**
	 * Saves earn rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 * @throws \RouteException If the user is not found.
	 */
	protected function get_route_post_response(\WP_REST_Request $request)
	{
		$user_id = $request->get_param('userId');

		$user = get_user_by('id', $user_id);

		if (! $user) {
			throw new RouteException('join-program', 'User not found', 400);
		}

		$email = $user->user_email;

		if (! $email) {
			throw new RouteException('join-program', 'User email not found', 400);
		}

		$contact = $this->connection->find_or_create_contact($email);
		$uuid    = $contact['uuid'];

		$this->connection->sync_user_attributes($user_id, $uuid);

		return rest_ensure_response(
			[
				'success' => true,
				'message' => 'Successfully joined the program',
			]
		);
	}
}
