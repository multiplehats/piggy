<?php

namespace PiggyWP\Api\Routes\V1;

use PiggyWP\Api\Routes\V1\AbstractRoute;
use WP_REST_Request;

/**
 * Shops class.
 *
 * @internal
 */
class Webhooks extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'webhooks';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'webhooks';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/webhooks';
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
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Receives the webhook event.
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response(WP_REST_Request $request) {
		$event = $request['event'];
		$data = $request->get_json_params();

		// Handle the webhook event here
		// Example: Log the event data
		error_log("Received webhook for event: $event");
		error_log(print_r($data, true));

		return new \WP_REST_Response(['status' => 'success'], 200);
	}
}
