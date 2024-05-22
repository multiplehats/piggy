<?php
namespace PiggyWP\Api;

use Piggy\Api\RegisterClient;

class Connection {
	/**
	 * Piggy Register Client instance.
	 *
	 * @var RegisterClient
	 */
	protected $client;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$api_key = $this->get_api_key();

		if( $api_key ) {
			$this->client = new RegisterClient($api_key);
		} else {
			$this->client = null;
		}
	}

	/**
	 * Get the Piggy API key.
	 *
	 * @return string|null The Piggy API key.
	 */
	public function get_api_key() {
		$api_key = get_option('piggy_api_key', null);

		return $api_key;
	}

	/**
	 * Get the Piggy Register Client instance.
	 *
	 * @return RegisterClient
	 */
	public function get_client() {
		return $this->client;
	}
}
