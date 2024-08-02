<?php

namespace PiggyWP\Domain\Services;

use PiggyWP\Api\Connection;
use PiggyWP\Domain\Services\EarnRules;

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
	 * @var EarnRules
	 */
	private $earn_rules;

	/**
	 * CustomerSession constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct( Connection $connection, EarnRules $earn_rules )
	{
		$this->connection = $connection;
		$this->earn_rules = $earn_rules;

		add_action('woocommerce_created_customer', [$this, 'handle_customer_creation'], 10, 3);
		add_action('show_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('edit_user_profile', [$this, 'show_uuid_on_profile']);
		add_action('wp_login', [$this, 'sync_uuid_on_login'], 10, 2);
	}

	public function handle_customer_creation($wp_user_id, $new_customer_data, $password_generated)
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

		$this->connection->update_user_meta_uuid($uuid, $wp_user_id);
		$this->update_piggy_contact($uuid, $wp_user_id);

		// Fetch and log earn rules of type 'CREATE_ACCOUNT'
		$earn_rules = $this->earn_rules->get_earn_rules_by_type('CREATE_ACCOUNT');

		if ($earn_rules) {
			// Here we have at least one earn rule of type 'CREATE_ACCOUNT'. We always grab the first one
			// We check $earnRule['credits']['value'] to see how much credit we should give
			$earn_rule = $earn_rules[0];

			if ($earn_rule['credits']['value'] > 0) {
				$credits = $earn_rule['credits']['value'];

				$result = $this->connection->apply_credits($uuid, $credits);

				// // If result is false, log error
				if ( ! $result) {
					error_log("Failed to apply $credits credits to user $wp_user_id");
				}
			}
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
						$uuid = $this->connection->get_contact_uuid_by_wp_id($user->ID);

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
		$uuid = $this->connection->get_contact_uuid_by_wp_id($user_id);

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

			$this->connection->update_user_meta_uuid($uuid, $user_id);
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
