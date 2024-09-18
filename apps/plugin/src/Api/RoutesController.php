<?php
namespace PiggyWP\Api;

use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Api\Connection;
use PiggyWP\Settings;

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
	 * Settings
	 *
	 * @var Settings
	 */
	protected $settings;

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
	public function __construct( SchemaController $schema_controller, Connection $connection, Settings $settings) {
		$this->schema_controller = $schema_controller;
		$this->connection = $connection;
		$this->settings = $settings;

		$this->routes = [
			'v1'      => [
				Routes\V1\EarnReward::IDENTIFIER => Routes\V1\EarnReward::class,
				Routes\V1\SpendRulesSync::IDENTIFIER => Routes\V1\SpendRulesSync::class,
				Routes\V1\EarnRules::IDENTIFIER => Routes\V1\EarnRules::class,
				Routes\V1\SpendRules::IDENTIFIER => Routes\V1\SpendRules::class,
				Routes\V1\SpendRulesClaim::IDENTIFIER => Routes\V1\SpendRulesClaim::class,
				Routes\V1\Coupons::IDENTIFIER => Routes\V1\Coupons::class,
				Routes\V1\JoinProgram::IDENTIFIER => Routes\V1\JoinProgram::class,
				Routes\V1\Contact::IDENTIFIER => Routes\V1\Contact::class,
			],
			'private' => [
				Routes\V1\Admin\Settings::IDENTIFIER => Routes\V1\Admin\Settings::class,
				Routes\V1\Admin\Shops::IDENTIFIER => Routes\V1\Admin\Shops::class,
				Routes\V1\Admin\Rewards::IDENTIFIER => Routes\V1\Admin\Rewards::class,
				Routes\V1\WCProductsSearch::IDENTIFIER => Routes\V1\WCProductsSearch::class,
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
			$this->connection,
			$this->settings
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
