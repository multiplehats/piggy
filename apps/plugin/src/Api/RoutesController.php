<?php
namespace PiggyWP\Api;

use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Api\Connection;

/**
 * RoutesController class.
 */
class RoutesController {
	/**
	 * Piggy schema_controller.
	 *
	 * @var SchemaController
	 */
	protected $schema_controller;

	/**
	 * Piggy connection.
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Piggy routes.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schema_controller Schema controller class passed to each route.
	 */
	public function __construct( SchemaController $schema_controller, Connection $connection) {
		$this->schema_controller = $schema_controller;
		$this->connection = $connection;

		$this->routes            = [
			'v1'      => [],
			'private' => [
				Routes\V1\Admin\Shops::IDENTIFIER => Routes\V1\Admin\Shops::class,
			],
		];
	}

	/**
	 * Register all Piggy API routes. This includes routes under specific version namespaces.
	 */
	public function register_all_routes() {
		$this->register_routes( 'v1', 'piggy/v1' );
		$this->register_routes( 'private', 'piggy/private' );
	}

	/**
	 * Get a route class instance.
	 *
	 * Each route class is instantized with the SchemaController instance, and its main Schema Type.
	 *
	 * @throws \Exception If the schema does not exist.
	 * @param string $name Name of schema.
	 * @param string $version API Version being requested.
	 * @return AbstractRoute
	 */
	public function get( $name, $version = 'v1' ) {
		$route = $this->routes[ $version ][ $name ] ?? false;

		if ( ! $route ) {
			throw new \Exception( "{$name} {$version} route does not exist" );
		}

		return new $route(
			$this->schema_controller,
			$this->schema_controller->get( $route::SCHEMA_TYPE, $route::SCHEMA_VERSION ),
			$this->connection
		);
	}

	/**
	 * Register defined list of routes with WordPress.
	 *
	 * @param string $version API Version being registered..
	 * @param string $namespace Overrides the default route namespace.
	 */
	protected function register_routes( $version = 'v1', $namespace = 'piggy/v1' ) {
		if ( ! isset( $this->routes[ $version ] ) ) {
			return;
		}
		$route_identifiers = array_keys( $this->routes[ $version ] );
		foreach ( $route_identifiers as $route ) {
			$route_instance = $this->get( $route, $version );
			$route_instance->set_namespace( $namespace );

			register_rest_route(
				$route_instance->get_namespace(),
				$route_instance->get_path(),
				$route_instance->get_args()
			);
		}
	}
}
