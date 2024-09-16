<?php
namespace PiggyWP\Api;

use PiggyWP\Registry\Container;
use PiggyWP\Api\Formatters;
use PiggyWP\Api\RoutesController;
use PiggyWP\Api\SchemaController;
use PiggyWP\Api\Schemas\ExtendSchema;
use PiggyWP\Settings;

/**
 * Api Main Class.
 */
final class Api {
	/**
	 * Init and hook in Piggy API functionality.
	 */
	public function init() {
		add_action(
			'rest_api_init',
			function() {
				self::container()->get( RoutesController::class )->register_all_routes();
			}
		);
	}

	/**
	 * Loads the DI container for Piggy API.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance. Note: this means all dependencies will be reconstructed.
	 * @return mixed
	 */
	public static function container( $reset = false ) {
		static $container;

		if ( $reset ) {
			$container = null;
		}

		if ( $container ) {
			return $container;
		}

		$container = new Container();

		$container->register(
			Settings::class,
			function () {
				return new Settings();
			}
		);

		$container->register(
			Connection::class,
			function () {
				return new Connection();
			}
		);

		$container->register(
			RoutesController::class,
			function ( $container ) {
				return new RoutesController(
					$container->get( SchemaController::class ),
					$container->get( Connection::class ),
					$container->get( Settings::class )
				);
			}
		);

		$container->register(
			SchemaController::class,
			function ( $container ) {
				return new SchemaController(
					$container->get( ExtendSchema::class ),
					$container->get( Settings::class )
				);
			}
		);

		$container->register(
			ExtendSchema::class,
			function ( $container ) {
				return new ExtendSchema(
					$container->get( Formatters::class )
				);
			}
		);
		$container->register(
			Formatters::class,
			function () {
				$formatters = new Formatters();

				return $formatters;
			}
		);
		return $container;
	}
}
