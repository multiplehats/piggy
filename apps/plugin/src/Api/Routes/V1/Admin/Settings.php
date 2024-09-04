<?php

namespace PiggyWP\Api\Routes\V1\Admin;

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
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'description' => __( 'Setting ID', 'piggy' ),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Update settings
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$settings = $request->get_param( 'settings' );

		if( ! $settings ) {
			return rest_ensure_response( null );
		}

		$result = $this->settings->update_settings($settings);

		return rest_ensure_response( $result );
	}

	/**
	 * Get a specific setting
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		if( $id ) {
			$setting = $this->settings->get_setting_by_id($id);

			if( ! $setting ) {
				return rest_ensure_response( null );
			}

			$setting = $this->prepare_item_for_response( $setting, $request );

			return rest_ensure_response( $setting );
		}

		$include_api_key = current_user_can( 'manage_options' );
		$all_settings = $this->settings->get_all_settings_with_values($include_api_key);

		// Returns settings as an object rather than an array.
		// This makes it easier to work with in the front-end.
		$return = [];
		foreach ( $all_settings as $item ) {
			$data = $this->prepare_item_for_response( $item, $request );
			$return[$item['id']] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $return );
	}
}
