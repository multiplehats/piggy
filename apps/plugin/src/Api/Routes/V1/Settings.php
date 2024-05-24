<?php

namespace PiggyWP\Api\Routes\V1;

use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Api\Routes\V1\Admin\Middleware;

/**
 * Shops class.
 *
 * @internal
 */
class Settings extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'settings';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'settings';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/settings';
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
				'args'                => [],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [
					'settings' => [
						'description' => __( 'Settings', 'piggy' ),
						'type'        => 'object',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Get shops
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$settings = $request->get_param( 'settings' );
		error_log( print_r( $settings, true ) );

		$returned_options = $this->options->save_all_options( $settings );

		return rest_ensure_response( $returned_options );
	}
}
