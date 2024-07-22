<?php

namespace PiggyWP\Domain\Services;

use PiggyWP\Api\Connection;
use WP_REST_Request;

/**
 * Class CustomerSession
 */
class CustomerSession
{
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * CustomerSession constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct( Connection $connection)
	{
		$this->connection = $connection;

		add_action('woocommerce_created_customer', [$this, 'handle_customer_creation'], 10, 3);
		add_action('show_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('edit_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('wp_login', [$this, 'sync_uuid_on_login'], 10, 2);
	}

	public function handle_customer_creation($customer_id, $new_customer_data, $password_generated)
	{
		$client = $this->connection->init_client();

		if(!$client) {
			return;
		}

		$email = $new_customer_data['user_email'];

		if (!$email) {
			return;
		}

		$contact = $this->connection->create_contact( $email );

		if( ! $contact ) {
			return;
		}

		$uuid = $contact['uuid'];

		update_user_meta($customer_id, 'piggy_uuid', $uuid);
		$this->update_piggy_contact($uuid, $customer_id);
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

	public function sync_uuid_on_login( $user_login, $user )
	{
		$client = $this->connection->init_client();

		if(!$client) {
			return;
		}

		$user_id = $user->ID;
		$uuid = get_user_meta($user_id, 'piggy_uuid', true);

		if (!$uuid) {
			$email = $user->user_email;

			if (!$email) {
				return;
			}

			$contact = $this->connection->create_contact( $email );

			if( ! $contact ) {
				return;
			}

			$uuid = $contact['uuid'];

			update_user_meta($user_id, 'piggy_uuid', $uuid);
			$this->update_piggy_contact($uuid, $user_id);
		} else {
			$this->update_piggy_contact($uuid, $user_id);
		}
	}

	private function update_piggy_contact( $uuid, $user_id )
	{
		$attributes = [
			'wp_user_id' => $user_id,
		];

		return $this->connection->update_contact( $uuid, $attributes );
	}
}
