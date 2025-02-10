<?php

namespace Leat\Domain;

use Leat\Api\Connection;
use Leat\Assets\Api as AssetApi;
use Leat\Assets\AssetDataRegistry;
use Leat\AssetsController;
use Leat\Installer;
use Leat\Registry\Container;
use Leat\Migration;
use Leat\Api\Api;
use Leat\Domain\Services\EarnRules;
use Leat\Domain\Services\PromotionRules;
use Leat\Domain\Services\SpendRules;
use Leat\Domain\Syncing\SyncVouchers;
use Leat\Domain\Syncing\SyncPromotions;
use Leat\Domain\Syncing\SyncRewards;
use Leat\Domain\Services\WebhookManager;
use Leat\Domain\Services\LoyaltyManager;
use Leat\Domain\Services\Customer\CustomerAttributeSync;
use Leat\Domain\Services\Customer\CustomerCreationHandler;
use Leat\Domain\Services\Customer\CustomerProfileDisplay;
use Leat\Domain\Services\Order\OrderProcessor;
use Leat\Domain\Services\Order\OrderCreditHandler;
use Leat\Domain\Services\Cart\CartManager;
use Leat\Domain\Services\GiftcardProduct;
use Leat\PostTypeController;
use Leat\Settings;
use Leat\Shortcodes\CustomerDashboardShortcode;
use Leat\Shortcodes\RewardPointsShortcode;
use Leat\RedirectController;
use Leat\Utils\Logger;

/**
 * Takes care of bootstrapping the plugin.
 */
class Bootstrap
{

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
	 * Holds the Logger instance
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param Container $container  The Dependency Injection Container.
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->package   = $container->get(Package::class);
		$this->migration = $container->get(Migration::class);

		if ($this->has_core_dependencies()) {
			/**
			 * Leat depends on the WooCommerce plugin (also included in WooCommerce core as of 6.4).
			 */
			add_action(
				'woocommerce_blocks_loaded',
				function () {
					$this->init();
					/**
					 * Fires after the Leat plugin has loaded.
					 *
					 * This hook is intended to be used as a safe event hook for when the plugin has been loaded, and all
					 * dependency requirements have been met.
					 */
					do_action('leat_loaded');
				}
			);
		}
	}

	/**
	 * Init the package and define constants.
	 */
	protected function init()
	{
		/**
		 * Action triggered before Leat initialization begins.
		 */
		do_action('leat_before_init');

		$this->register_dependencies();

		if (is_admin()) {
			if ($this->package->get_version() !== $this->package->get_version_stored_on_db()) {
				$this->migration->run_migrations();
				$this->package->set_version_stored_on_db();
			}
		}

		$is_rest = wc()->is_rest_api_request();

		// Load and init assets.
		$this->container->get(Api::class)->init();

		// Load assets in admin and on the frontend.
		if (! $is_rest) {
			$this->add_build_notice();
			$this->container->get(AssetDataRegistry::class);
			$this->container->get(Installer::class)->init();
			$this->container->get(AssetsController::class);
			$this->container->get(PostTypeController::class);
			// $this->container->get(RedirectController::class)->init();
		}
		$this->container->get(CustomerDashboardShortcode::class)->init();
		$this->container->get(RewardPointsShortcode::class)->init();
		$this->container->get(LoyaltyManager::class);
		$this->container->get(SyncVouchers::class)->init();
		$this->container->get(SyncPromotions::class)->init();
		$this->container->get(SyncRewards::class)->init();
		$this->container->get(GiftcardProduct::class)->init();
		$this->container->get(WebhookManager::class)->init();

		/**
		 * Action triggered after Leat initialization finishes.
		 */
		do_action('leat_init');
	}

	/**
	 * Check core dependencies exist.
	 *
	 * @return boolean
	 */
	protected function has_core_dependencies()
	{
		$has_needed_dependencies = class_exists('WooCommerce', false);

		if ($has_needed_dependencies) {
			$plugin_data = \get_file_data(
				$this->package->get_path('leat-crm.php'),
				[
					'RequiredWCVersion' => 'WC requires at least',
				]
			);

			if (isset($plugin_data['RequiredWCVersion']) && version_compare(\WC()->version, $plugin_data['RequiredWCVersion'], '<')) {
				$has_needed_dependencies = false;
				add_action(
					'admin_notices',
					function () use ($plugin_data) {
						if (leat_should_display_compatibility_notices()) {
?>
						<div class="notice notice-error">
							<p>
								<?php
								/* translators: %s: Required WooCommerce version */
								printf(esc_html__('The Leat plugin requires at least version %s of WooCommerce and has been deactivated. Please update WooCommerce.', 'leat-crm'), esc_html($plugin_data['RequiredWCVersion']));
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
	protected function is_built()
	{
		$dev_files = array(
			'dist/admin/vite-dev-server.json',
			'dist/frontend/vite-dev-server.json',
		);

		$prod_files = array(
			'dist/admin/manifest.json',
			'dist/frontend/manifest.json',
		);

		foreach ($dev_files as $dev_file) {
			if (file_exists($this->package->get_path($dev_file))) {
				return true;
			}
		}

		foreach ($prod_files as $prod_file) {
			if (file_exists($this->package->get_path($prod_file))) {
				if (file_exists($this->package->get_path('dist/frontend/assets')) && file_exists($this->package->get_path('dist/admin/assets'))) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Add a notice stating that the build has not been done yet.
	 */
	protected function add_build_notice()
	{
		if ($this->is_built()) {
			return;
		}
		add_action(
			'admin_notices',
			function () {
				echo '<div class="error"><p>';
				printf(
					/* translators: %1$s is the install command, %2$s is the build command, %3$s is the watch command. */
					esc_html__('Leat requires files to be built—it looks like the dist folder is empty. From the plugin directory, run %1$s to install dependencies, %2$s to build the files or %3$s to build the files and watch for changes.', 'leat-crm'),
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
	protected function register_dependencies()
	{
		$this->container->register(
			Logger::class,
			function (Container $container) {
				return new Logger('leat');
			}
		);

		$this->container->register(
			PostTypeController::class,
			function (Container $container) {
				return new PostTypeController();
			}
		);
		$this->container->register(
			Settings::class,
			function (Container $container) {
				return new Settings();
			}
		);
		$this->container->register(
			RedirectController::class,
			function () {
				return new RedirectController();
			}
		);
		$this->container->register(
			AssetApi::class,
			function (Container $container) {
				return new AssetApi($container->get(Package::class));
			}
		);
		$this->container->register(
			AssetDataRegistry::class,
			function (Container $container) {
				return new AssetDataRegistry($container->get(AssetApi::class));
			}
		);
		$this->container->register(
			Connection::class,
			function (Container $container) {
				return new Connection();
			}
		);
		$this->container->register(
			EarnRules::class,
			function (Container $container) {
				return new EarnRules();
			}
		);
		$this->container->register(
			PromotionRules::class,
			function (Container $container) {
				return new PromotionRules($container->get(Connection::class));
			}
		);
		$this->container->register(
			SpendRules::class,
			function (Container $container) {
				return new SpendRules();
			}
		);
		$this->container->register(
			SyncVouchers::class,
			function (Container $container) {
				return new SyncVouchers($container->get(Connection::class), $container->get(PromotionRules::class));
			}
		);
		$this->container->register(
			SyncPromotions::class,
			function (Container $container) {
				return new SyncPromotions($container->get(Connection::class), $container->get(PromotionRules::class));
			}
		);
		$this->container->register(
			SyncRewards::class,
			function (Container $container) {
				return new SyncRewards($container->get(Connection::class), $container->get(SpendRules::class));
			}
		);
		$this->container->register(
			AssetsController::class,
			function (Container $container) {
				return new AssetsController($container->get(AssetApi::class), $container->get(Settings::class,), $container->get(Connection::class));
			}
		);
		$this->container->register(
			CartManager::class,
			function (Container $container) {
				return new CartManager($container->get(SpendRules::class), $container->get(Logger::class));
			}
		);
		$this->container->register(
			CustomerAttributeSync::class,
			function (Container $container) {
				return new CustomerAttributeSync(
					$container->get(Connection::class),
					$container->get(Logger::class)
				);
			}
		);
		$this->container->register(
			CustomerCreationHandler::class,
			function (Container $container) {
				return new CustomerCreationHandler(
					$container->get(Connection::class),
					$container->get(EarnRules::class),
					$container->get(Logger::class),
				);
			}
		);
		$this->container->register(
			CustomerProfileDisplay::class,
			function (Container $container) {
				return new CustomerProfileDisplay(
					$container->get(Connection::class),
					$container->get(Logger::class),
				);
			}
		);
		$this->container->register(
			OrderProcessor::class,
			function (Container $container) {
				return new OrderProcessor(
					$container->get(Connection::class),
					$container->get(EarnRules::class)
				);
			}
		);
		$this->container->register(
			OrderCreditHandler::class,
			function (Container $container) {
				return new OrderCreditHandler(
					$container->get(Connection::class)
				);
			}
		);
		$this->container->register(
			LoyaltyManager::class,
			function (Container $container) {
				return new LoyaltyManager(
					$container->get(Logger::class),
					$container->get(Connection::class),
					$container->get(EarnRules::class),
					$container->get(SpendRules::class),
					$container->get(Settings::class),
					$container->get(CustomerAttributeSync::class),
					$container->get(CustomerCreationHandler::class),
					$container->get(CustomerProfileDisplay::class),
					$container->get(OrderProcessor::class),
					$container->get(OrderCreditHandler::class),
					$container->get(CartManager::class)
				);
			}
		);
		$this->container->register(
			GiftcardProduct::class,
			function (Container $container) {
				return new GiftcardProduct($container->get(Connection::class), $container->get(Settings::class));
			}
		);
		$this->container->register(
			Installer::class,
			function (Container $container) {
				return new Installer(
					$container->get(WebhookManager::class)
				);
			}
		);
		$this->container->register(
			WebhookManager::class,
			function (Container $container) {
				return new WebhookManager($container->get(Connection::class));
			}
		);
		$this->container->register(
			CustomerDashboardShortcode::class,
			function (Container $container) {

				return new CustomerDashboardShortcode($container->get(AssetApi::class));
			}
		);
		$this->container->register(
			RewardPointsShortcode::class,
			function (Container $container) {
				return new RewardPointsShortcode(
					$container->get(AssetApi::class),
					$container->get(Connection::class),
					$container->get(Settings::class)
				);
			}
		);
		$this->container->register(
			Api::class,
			function (Container $container) {
				return new Api(
					$container->get(Connection::class),
					$container->get(Settings::class),
					$container->get(PromotionRules::class),
					$container->get(SpendRules::class),
					$container->get(SyncVouchers::class),
					$container->get(SyncPromotions::class),
					$container->get(SyncRewards::class),
					$container->get(WebhookManager::class)
				);
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
	protected function deprecated_dependency($function, $version, $replacement = '', $trigger_error_version = '')
	{
		if (! defined('WP_DEBUG') || ! WP_DEBUG) {
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
		 */
		do_action('deprecated_function_run', $function, $replacement, $version);

		$log_error = false;

		// If headers have not been sent yet, log to avoid breaking the request.
		if (! headers_sent()) {
			$log_error = true;
		}

		// If the $trigger_error_version was not yet reached, only log the error.
		if (version_compare($this->package->get_version(), $trigger_error_version, '<')) {
			$log_error = true;
		}

		/**
		 * Filters whether to trigger a PHP error for deprecated dependencies.
		 */
		if (! apply_filters('deprecated_function_trigger_error', true)) {
			$log_error = true;
		}

		if ($log_error) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log($error_message);
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error($error_message, E_USER_DEPRECATED);
		}
	}
}
