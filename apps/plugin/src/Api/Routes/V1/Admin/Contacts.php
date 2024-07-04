<?php

namespace PiggyWP\Api\Routes\V1\Admin;

use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Api\Routes\V1\Admin\Middleware;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use PiggyWP\Api\Exceptions\RouteException;
use WP_REST_Request;

/**
 * Contacts class.
 *
 * @internal
 */
class Contacts extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'contacts';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'contacts';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/contacts';
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
				'permission_callback' => '__return_true',
				'args'                => [
					'email' => [
						'description' => __( 'Email', 'piggy' ),
						'type'        => 'string',
					],
					'referral_code' => [
						'description' => __( 'Referral code', 'piggy' ),
						'type'        => 'string',
					],
				],
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => $this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),

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
	 * Create a new contact.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$this->init_client();

		$email = $request->get_param( 'email' );

		$contact = Contact::findOrCreate( array( 'email' => $email ) );

		if( ! $contact ) {
			return new RouteException( 'piggy_contact_not_created', __( 'Contact not created', 'piggy' ), [ 'status' => 500 ] );
		}

		if( is_array( $contact ) && $contact['data']['status'] !== 200 ) {
			return new RouteException( 'piggy_contact_not_created', __( 'Contact not created', 'piggy' ), [ 'status' => 500 ] );
		}

		$result = $this->schema->get_item_response( $contact );

		return rest_ensure_response( $result );
	}

	/**
	 * Update a contact.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_update_response(WP_REST_Request $request) {
		$this->init_client();

		$id = $request->get_param('id');
		$attributes = $request->get_param('attributes');

		// This throws an error, but we need to check if the attribute exists first, then create it.
		// THis probably should be moved to the onboarding or api key setup process.

		// $attributes_list = CustomAttribute::list(["entity" => "contact"]);
		// $result = CustomAttribute::create(["entity" => "contact", "name" => "wp_user_id", "label" => "WordPress User ID", "type" => "text" ]);
		$contact = Contact::update( $id, [ "attributes" => $attributes ] );

		if( ! $contact ) {
			return new RouteException( 'piggy_contact_not_updated', __( 'Contact not updated', 'piggy' ), [ 'status' => 500 ] );
		}

		if( is_array( $contact ) && $contact['data']['status'] !== 200 ) {
			return new RouteException( 'piggy_contact_not_updated', __( 'Contact not updated', 'piggy' ), [ 'status' => 500 ] );
		}

		return rest_ensure_response( true );
	}

	/**
	 * Get a contact.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$this->init_client();

		$id = $request->get_param( 'id' );

		$contact = Contact::get( $id );

		if( ! $contact ) {
			return new RouteException( 'piggy_contact_not_found', __( 'Contact not found', 'piggy' ), [ 'status' => 404 ] );
		}

		$result = $this->schema->get_item_response( $contact );

		return rest_ensure_response( $result );
	}

}
