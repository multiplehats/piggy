<?php
namespace PiggyWP\Api;

use Piggy\Api\RegisterClient;
use Piggy\Api\ApiClient;

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
	 * @return null|true
	 */
	public function init_client() {
		$api_key = $this->get_api_key();

		if( $api_key ) {
			ApiClient::configure($api_key, "https://api.piggy.eu");

			return $this->client = true;
		} else {
			return $this->client = null;
		}
	}
}
