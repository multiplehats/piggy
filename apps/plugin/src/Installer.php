<?php
namespace PiggyWP;

use PiggyWP\Api\Connection;

/**
 * Installer class.
 * Handles installation of Blocks plugin dependencies.
 *
 * @internal
 */
class Installer {
	/**
	 * Piggy connection.
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->connection = new Connection();
	}
}
