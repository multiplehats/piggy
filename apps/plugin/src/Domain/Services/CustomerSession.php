<?php

namespace PiggyWP\Domain\Services;

use WP_REST_Request;

/**
 * Class CustomerSession
 */
class CustomerSession
{
	public function __construct()
	{
		add_action('woocommerce_created_customer', [$this, 'handle_customer_creation'], 10, 3);
		add_action('show_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('edit_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('wp_login', [$this, 'sync_uuid_on_login'], 10, 2);
	}

	public function handle_customer_creation($customer_id, $new_customer_data, $password_generated)
	{
		$email = $new_customer_data['user_email'];

		if (!$email) {
			return;
		}

		$request = new WP_REST_Request('POST', '/piggy/private/contacts');
		$request->set_body_params(['email' => $email]);
		$response = rest_do_request($request);
		$server = rest_get_server();
		$data = $server->response_to_data($response, false);

		if (is_wp_error($response) || empty($data) || isset($data['code'])) {
			error_log('Error creating piggy contact: ' . json_encode($data));
			return;
		}

		if (isset($data['uuid'])) {
			$uuid = $data['uuid'];

			update_user_meta($customer_id, 'piggy_uuid', $uuid);
			$this->update_piggy_contact($uuid, $customer_id);
		}
	}

	public function show_uuid_on_profile($user)
	{
?>
		<h3>Piggy</h3>
		<table class="form-table">
			<tr>
				<th><label for="piggy_uuid">Contact ID</label></th>
				<td>
					<?php
					$uuid = get_user_meta($user->ID, 'piggy_uuid', true);
					echo $uuid ? $uuid : '—';
					?>
				</td>
			</tr>
		</table>
<?php
	}

	public function sync_uuid_on_login($user_login, $user)
	{
		$user_id = $user->ID;
		$uuid = get_user_meta($user_id, 'piggy_uuid', true);

		if (!$uuid) {
			$email = $user->user_email;

			if (!$email) {
				return;
			}

			$request = new WP_REST_Request('POST', '/piggy/private/contacts');
			$request->set_body_params(['email' => $email]);
			$response = rest_do_request($request);
			$server = rest_get_server();
			$data = $server->response_to_data($response, false);

			if (is_wp_error($response) || empty($data) || isset($data['code'])) {
				error_log('Error syncing piggy UUID on login: ' . json_encode($data));
				return;
			}

			if (isset($data['uuid'])) {
				$uuid = $data['uuid'];

				update_user_meta($user_id, 'piggy_uuid', $uuid);
				$this->update_piggy_contact($uuid, $user_id);
			}
		} else {
			$this->update_piggy_contact($uuid, $user_id);
		}
	}

	private function update_piggy_contact($uuid, $user_id)
	{
		$request = new WP_REST_Request('PUT', '/piggy/private/contacts');
		$request->set_body_params(['id' => $uuid, 'attributes' => ['wp_user_id' => strval($user_id)]]);
		$response = rest_do_request($request);
		$server = rest_get_server();
		$data = $server->response_to_data($response, false);

		if (is_wp_error($response) || empty($data) || isset($data['code'])) {
			error_log('Error updating piggy contact: ' . json_encode($data));
			return false;
		}

		return $data;
	}
}
