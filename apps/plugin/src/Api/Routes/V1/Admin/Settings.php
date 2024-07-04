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

		foreach( $settings as $setting ) {
			$value = $setting['value'];

			if( $setting['type'] === 'translatable_text' && is_array( $value ) ) {
				$value = json_encode( $value );
			}

			if( $setting['type'] === 'checkboxes' && is_array( $value ) ) {
				$value = json_encode( $value );
			}

			update_option( 'piggy_' . $setting['id'], $value );
		}

		return rest_ensure_response( true );
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
		$all_settings = $this->settings->get_all_settings();

		// Remove "api_key" from settings if user is not an admin.
		if( ! current_user_can( 'manage_options' ) ) {
			$all_settings = array_filter($all_settings, function($setting) {
				return $setting['id'] !== 'api_key';
			});
		}

		if( $id ) {
			$item = array_filter($all_settings, function($setting) use ($id) {
				return $setting['id'] === $id;
			});

			if( ! $item ) {
				return rest_ensure_response( null );
			}

			$item = reset( $item );

			$settings = $this->prepare_item_for_response( $item, $request );

			return rest_ensure_response( $settings );
		}

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
