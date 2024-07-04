<?php

namespace PiggyWP\Domain\Services;

use WP_REST_Request;

/**
 * Class CustomerCreation
 */
class CustomerCreation {

	public function __construct() {
		add_action('woocommerce_created_customer', [ $this, 'handle_customer_creation' ], 10, 3);
		add_action('show_user_profile', [ $this, 'show_uuid_on_profile']);
		add_action('edit_user_profile', [ $this, 'show_uuid_on_profile']);
	}

	public function handle_customer_creation( $customer_id, $new_customer_data, $password_generated ) {
		$email = $new_customer_data['user_email'];

		if( ! $email ) {
			return;
		}

		// Create a new WP REST request
		$request = new WP_REST_Request( 'POST', '/piggy/private/contacts' );
		$request->set_body_params( [ 'email' => $email ] );

		// Execute the request
		$response = rest_do_request( $request );
		$server = rest_get_server();
		$data = $server->response_to_data( $response, false );

		if( isset( $data['uuid'] ) ) {
			$uuid = $data['uuid'];

			// Store the UUID in the user meta
			update_user_meta( $customer_id, 'piggy_uuid', $uuid );

            // Update the Piggy contact with the WP user ID.
            $request = new WP_REST_Request( 'PUT', '/piggy/private/contacts' );
            $request->set_body_params( [ 'id' => $uuid, 'attributes' => [ 'wp_user_id' => strval( $customer_id ) ] ] );
            $response = rest_do_request( $request );
            $server = rest_get_server();
            $data = $server->response_to_data( $response, false );
		}
	}

	public function show_uuid_on_profile($user) {
		?>
		<h3>Piggy</h3>
		<table class="form-table">
			<tr>
				<th><label for="piggy_uuid">Contact ID</label></th>
				<td>
					<?php
					$uuid = get_user_meta( $user->ID, 'piggy_uuid', true );
					echo $uuid ? $uuid : '—';
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
