<?php
namespace PiggyWP\Domain;

use PiggyWP\Options;
use PiggyWP\Assets\Api as AssetApi;
use PiggyWP\Assets\AssetDataRegistry;
use PiggyWP\Utils\AdminUtils;
use PiggyWP\AssetsController;
use PiggyWP\CustomizerController;
use PiggyWP\AjaxController;
use PiggyWP\Installer;
use PiggyWP\Registry\Container;
use PiggyWP\Migration;
use PiggyWP\Domain\Services\OrderContext;
use PiggyWP\Shortcodes\CartLauncherShortcode;
use PiggyWP\StoreApiExtension\Api as StoreApiExtensionApi;
use PiggyWP\StoreApiExtension\StoreApiExtensionRegistry;
use PiggyWP\StoreApiExtension\Core\FreeShippingMeter;
use PiggyWP\StoreApiExtension\Core\ProductSuggestions;
use PiggyWP\StoreApiExtension\Core\Common;
use PiggyWP\Api\Api;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\StoreApi;
use PiggyWP\PostTypeController;
use PiggyWP\Settings;
use PiggyWP\StoreApiExtension\Compat\CompatRegistry;

/**
 * Takes care of bootstrapping the plugin.
 *
 * @since 2.5.0
 */
class Bootstrap {

	/**
	 * Holds the Dependency Injection Container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Holds the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Holds the Migration instance
	 *
	 * @var Migration
	 */
	private $migration;

	/**
	 * Constructor
	 *
	 * @param Container $container  The Dependency Injection Container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
		$this->package   = $container->get( Package::class );
		$this->migration = $container->get( Migration::class );

		if ( $this->has_core_dependencies() ) {
			/**
			 * Piggy depends on the WooCommerce Blocks plugin (also included in WooCommerce core as of 6.4).
			 */
			add_action(
				'woocommerce_blocks_loaded',
				function() {
					$this->init();
					/**
					 * Fires after the Piggy plugin has loaded.
					 *
					 * This hook is intended to be used as a safe event hook for when the plugin has been loaded, and all
					 * dependency requirements have been met.
					 *
					 * @since 1.0.0
					 */
					do_action( 'piggy_loaded' );
				}
			);
		}
	}

	/**
	 * Init the package and define constants.
	 */
	protected function init() {
		/**
		 * Action triggered before Piggy initialization begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'before_piggy_init' );

		$this->register_dependencies();
		$this->register_api_route_extensions();

		if ( is_admin() ) {
			if ( $this->package->get_version() !== $this->package->get_version_stored_on_db() ) {
				$this->migration->run_migrations();
				$this->package->set_version_stored_on_db();
			}
		}

		$is_rest = wc()->is_rest_api_request();

		// Load and init assets.
		$this->container->get( Api::class )->init();

		// Load assets in admin and on the frontend.
		if ( ! $is_rest ) {
			$this->add_build_notice();
			$this->container->get( AssetDataRegistry::class );
			$this->container->get( Installer::class );
			$this->container->get( AssetsController::class );
			$this->container->get (PostTypeController::class);
			$this->container->get( AjaxController::class );
		}
		$this->container->get( OrderContext::class )->init();
		$this->container->get( CartLauncherShortcode::class )->init();
		$this->container->get( StoreApiExtensionApi::class );

		/**
		* Action triggered after Piggy initialization finishes.
		*
		* @since 1.0.0
		*/
		do_action( 'piggy_init' );
	}

	/**
	 * Check core dependencies exist.
	 *
	 * @return boolean
	 */
	protected function has_core_dependencies() {
		$has_needed_dependencies = class_exists( 'WooCommerce', false );

		if ( $has_needed_dependencies ) {
			$plugin_data = \get_file_data(
				$this->package->get_path( 'piggy.php' ),
				[
					'RequiredWCVersion' => 'WC requires at least',
				]
			);

			if ( isset( $plugin_data['RequiredWCVersion'] ) && version_compare( \WC()->version, $plugin_data['RequiredWCVersion'], '<' ) ) {
				$has_needed_dependencies = false;
				add_action(
					'admin_notices',
					function() use ( $plugin_data ) {
						if ( should_display_compatibility_notices() ) {
							?>
							<div class="notice notice-error">
								<p>
								<?php
									/* translators: %s: Required WooCommerce version */
									printf( esc_html__( 'The Piggy plugin requires at least version %s of WooCommerce and has been deactivated. Please update WooCommerce.', 'piggy' ), esc_html( $plugin_data['RequiredWCVersion'] ) );
								?>
								</p>
							</div>
							<?php
						}
					}
				);
			}
		}

		return $has_needed_dependencies;
	}

	/**
	 * See if files have been built or not.
	 *
	 * @return bool
	 */
	protected function is_built() {
		$dev_files = array(
			'dist/admin/vite-dev-server.json',
			'dist/frontend/vite-dev-server.json',
		);

		$prod_files = array(
			'dist/admin/manifest.json',
			'dist/frontend/manifest.json',
		);

		foreach ( $dev_files as $dev_file ) {
			if ( file_exists( $this->package->get_path( $dev_file ) ) ) {
				return true;
			}
		}

		foreach ( $prod_files as $prod_file ) {
			if ( file_exists( $this->package->get_path( $prod_file ) ) ) {
				if ( file_exists( $this->package->get_path( 'dist/frontend/assets' ) ) && file_exists( $this->package->get_path( 'dist/admin/assets' ) ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Add a notice stating that the build has not been done yet.
	 */
	protected function add_build_notice() {
		if ( $this->is_built() ) {
			return;
		}
		add_action(
			'admin_notices',
			function() {
				echo '<div class="error"><p>';
				printf(
					/* translators: %1$s is the install command, %2$s is the build command, %3$s is the watch command. */
					esc_html__( 'Piggy requires files to be built—it looks like the dist folder is empty. From the plugin directory, run %1$s to install dependencies, %2$s to build the files or %3$s to build the files and watch for changes.', 'piggy' ),
					'<code>pnpm install</code>',
					'<code>pnpm run build</code>',
					'<code>pnpm start</code>'
				);
				echo '</p></div>';
			}
		);
	}

	/**
	 * Register core dependencies with the container.
	 */
	protected function register_dependencies() {
		$this->container->register(
			PostTypeController::class,
			function( Container $container ) {
				return new PostTypeController();
			}
		);
		$this->container->register(
			Options::class,
			function ( Container $container ) {
				return new Options();
			}
		);
		$this->container->register(
			Settings::class,
			function ( Container $container ) {
				return new Settings();
			}
		);
		$this->container->register(
			AdminUtils::class,
			function ( Container $container ) {
				return new AdminUtils( $container->get( Options::class ) );
			}
		);
		$this->container->register(
			AssetApi::class,
			function ( Container $container ) {
				return new AssetApi( $container->get( Package::class ) );
			}
		);
		$this->container->register(
			AssetApi::class,
			function ( Container $container ) {
				return new AssetApi( $container->get( Package::class ) );
			}
		);
		$this->container->register(
			AssetDataRegistry::class,
			function( Container $container ) {
				return new AssetDataRegistry( $container->get( AssetApi::class ) );
			}
		);
		$this->container->register(
			AssetsController::class,
			function( Container $container ) {
				return new AssetsController( $container->get( AssetApi::class ), $container->get( Options::class ) );
			}
		);
		$this->container->register(
			CustomizerController::class,
			function( Container $container ) {
				return new CustomizerController( $container->get( AssetApi::class ), $container->get( Options::class ) );
			}
		);
		$this->container->register(
			AjaxController::class,
			function( Container $container ) {
				return new AjaxController( $container->get( Options::class ), $container->get( AdminUtils::class ) );
			}
		);
		$this->container->register(
			OrderContext::class,
			function( Container $container ) {
				return new OrderContext( $container->get( Package::class ) );
			}
		);
		$this->container->register(
			StoreApiExtensionRegistry::class,
			function() {
				return new StoreApiExtensionRegistry();
			}
		);
		$this->container->register(
			Installer::class,
			function () {
				return new Installer();
			}
		);
		$this->container->register(
			CartLauncherShortcode::class,
			function ( Container $container ) {
				$asset_api            = $container->get( AssetApi::class );

				return new CartLauncherShortcode( $asset_api );
			}
		);
		$this->container->register(
			StoreApiExtensionApi::class,
			function ( Container $container ) {
				$store_api_registry = $container->get( StoreApiExtensionRegistry::class );
				$options            = $container->get( Options::class );
				$extend_schema      = StoreApi::container()->get( ExtendSchema::class );

				return new StoreApiExtensionApi( $store_api_registry, $extend_schema, $options );
			}
		);
		$this->container->register(
			Api::class,
			function () {
				return new Api();
			}
		);
	}

	/**
	 * Throws a deprecation notice for a dependency without breaking requests.
	 *
	 * @param string $function Class or function being deprecated.
	 * @param string $version Version in which it was deprecated.
	 * @param string $replacement Replacement class or function, if applicable.
	 * @param string $trigger_error_version Optional version to start surfacing this as a PHP error rather than a log. Defaults to $version.
	 */
	protected function deprecated_dependency( $function, $version, $replacement = '', $trigger_error_version = '' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$trigger_error_version = $trigger_error_version ? $trigger_error_version : $version;
		$error_message         = $replacement ? sprintf(
			'%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
			$function,
			$version,
			$replacement
		) : sprintf(
			'%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
			$function,
			$version
		);

		/**
		 * Filters the deprecation notice for a dependency.
		 *
		 * @param string $error_message The error message.
		 * @since 1.0.0
		 */
		do_action( 'deprecated_function_run', $function, $replacement, $version );

		$log_error = false;

		// If headers have not been sent yet, log to avoid breaking the request.
		if ( ! headers_sent() ) {
			$log_error = true;
		}

		// If the $trigger_error_version was not yet reached, only log the error.
		if ( version_compare( $this->package->get_version(), $trigger_error_version, '<' ) ) {
			$log_error = true;
		}

		/**
		 * Filters whether to trigger a PHP error for deprecated dependencies.
		 *
		 * @since 1.0.0
		 */
		if ( ! apply_filters( 'deprecated_function_trigger_error', true ) ) {
			$log_error = true;
		}

		if ( $log_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $error_message );
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( $error_message, E_USER_DEPRECATED );
		}
	}

	/**
	 * Register payment method integrations with the container.
	 */
	protected function register_api_route_extensions() {
		$this->container->register(
			FreeShippingMeter::class,
			function ( Container $container ) {
				$extend_schema     = StoreApi::container()->get( ExtendSchema::class );
				$schema_controller = StoreApi::container()->get( SchemaController::class );
				$options           = $container->get( Options::class );

				return new FreeShippingMeter( $extend_schema, $schema_controller, $options );
			}
		);
		$this->container->register(
			ProductSuggestions::class,
			function ( Container $container ) {
				$extend_schema     = StoreApi::container()->get( ExtendSchema::class );
				$schema_controller = StoreApi::container()->get( SchemaController::class );
				$options           = $container->get( Options::class );

				return new ProductSuggestions( $extend_schema, $schema_controller, $options );
			}
		);
		$this->container->register(
			CompatRegistry::class,
			function ( Container $container ) {
				$extend_schema     = StoreApi::container()->get( ExtendSchema::class );
				$schema_controller = StoreApi::container()->get( SchemaController::class );
				$options           = $container->get( Options::class );

				return new CompatRegistry( $extend_schema, $schema_controller, $options );
			}
		);
		$this->container->register(
			Common::class,
			function ( Container $container ) {
				$extend_schema     = StoreApi::container()->get( ExtendSchema::class );
				$schema_controller = StoreApi::container()->get( SchemaController::class );
				$options           = $container->get( Options::class );

				return new Common( $extend_schema, $schema_controller, $options );
			}
		);
	}
}
