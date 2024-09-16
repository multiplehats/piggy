<?php

namespace PiggyWP\Api\Routes\V1;

use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Api\Exceptions\RouteException;
use PiggyWP\Domain\Services\CustomerSession;

class JoinProgram extends AbstractRoute {
    const IDENTIFIER = 'join-program';
    const SCHEMA_TYPE = 'join-program';

    public function get_path() {
        return '/join-program';
    }

    public function get_args() {
        return [
            [
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'userId' => [
                        'required' => true,
                        'type'     => 'integer',
                    ],
                ],
            ],
            'schema' => [ $this->schema, 'get_public_item_schema' ],
        ];
    }

	/**
	 * Saves earn rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
        $user_id = $request->get_param('userId');

        $user = get_user_by('id', $user_id);

        if (!$user) {
            throw new RouteException('join-program', 'User not found', 400);
        }

        $email = $user->user_email;

        if (!$email) {
            throw new RouteException('join-program', 'User email not found', 400);
        }

        $result = $this->connection->create_contact($email);
        $uuid = $result['uuid'];

        $this->connection->update_user_meta_uuid($uuid, $user_id);
		$this->connection->sync_user_attributes($user_id, $uuid);

        if (!$result) {
            throw new RouteException('join-program', 'Failed to join the program', 500);
        }

        return rest_ensure_response(['success' => true, 'message' => 'Successfully joined the program']);
    }
}