<?php

/**
 * Plugin Name: Piggy - Loyalty & Marketing
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: Customer loyalty & Email marketing that works in-store and online.
 * Version: 0.0.7
 * Author: Piggy
 * Author URI: https://piggy.com
 * Text Domain: piggy
 * Requires at least: 5.9
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires PHP Architecture: 64 bits
 * Requires Plugins: woocommerce
 * WC requires at least: 6.9
 * WC tested up to: 8.8.3
 * Update URI:  https://wplatest.co
 *
 * @package Piggy
 */

defined( 'ABSPATH' ) || exit;

$minimum_wp_version = '6.0';

if ( ! defined( 'PIGGY_URL' ) ) {
	define( 'PIGGY_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'PIGGY_VERSION' ) ) {
	define( 'PIGGY_VERSION', '0.0.7' );
}

/**
 * Temporary solution to update the plugin using WPLatest Updater until
 * we release the plugin in the WordPress repository.
 */
add_action('init', function() {
	$options = array(
		'file'   => __FILE__,
		'id'     => 'plugin_t7jfygltvh47e88f6c6nfubb',
	);

	// new WPLatest\Updater\PluginUpdater($options);
});

/**
 * Declare support for HPOS (High-Performance Order Storage)
 */
add_action('before_woocommerce_init', function(){
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
});

/**
 * Whether notices must be displayed in the current page (plugins and WooCommerce pages).
 *
 * @since 2.5.0
 */
function should_display_compatibility_notices() {
	$current_screen = get_current_screen();

	if ( ! isset( $current_screen ) ) {
		return false;
	}

	$is_plugins_page     =
		property_exists( $current_screen, 'id' ) &&
		'plugins' === $current_screen->id;
	$is_woocommerce_page =
		property_exists( $current_screen, 'parent_base' ) &&
		'woocommerce' === $current_screen->parent_base;

	return $is_plugins_page || $is_woocommerce_page;
}

if ( version_compare( $GLOBALS['wp_version'], $minimum_wp_version, '<' ) ) {
	/**
	 * Outputs for an admin notice about running WooCommerce Blocks on outdated WordPress.
	 *
	 * @since 2.5.0
	 */
	function piggy_admin_unsupported_wp_notice() {
		if ( should_display_compatibility_notices() ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'The Piggy plugin requires a more recent version of WordPress and has been paused. Please update WordPress to continue enjoying Piggy.', 'piggy' ); ?></p>
			</div>
			<?php
		}
	}
	add_action( 'admin_notices', 'piggy_admin_unsupported_wp_notice' );
	return;
}

/**
 * Autoload packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';

if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(  // phpcs:ignore
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the Piggy plugin is incomplete. Please run %1$s within the %2$s directory.', 'piggy' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}

	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function () {
			?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: 1: composer command. 2: plugin directory */
					esc_html__( 'Your installation of the Piggy plugin is incomplete. Please run %1$s within the %2$s directory.', 'piggy' ),
					'<code>composer install</code>',
					'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
				);
				?>
			</p>
		</div>
			<?php
		}
	);
	return;
}

add_action( 'plugins_loaded', array( '\PiggyWP\Package', 'init' ) );
