<?php

/**
 * Plugin Name: Leat CRM
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: Customer loyalty & Email marketing that works in-store and online.
 * Version: 0.9.6
 * Author: rensleat, chrisjayden
 * Author URI: https://leat.com
 * Text Domain: leat-crm
 * Requires at least: 5.9
 * Domain Path: /languages
 * Stable tag: 0.9.6
 * Requires PHP: 8.0
 * Requires PHP Architecture: 64 bits
 * Requires Plugins: woocommerce
 * WC requires at least: 7.9.0
 * WC tested up to: 9.7.0
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright (c) 2024 Leat. All rights reserved.
 *
 * @package Leat
 */

defined('ABSPATH') || exit;

$minimum_wp_version = '6.0';

if (! defined('LEAT_URL')) {
	define('LEAT_URL', plugins_url('/', __FILE__));
}

if (! defined('LEAT_VERSION')) {
	define('LEAT_VERSION', '0.9.6');
}

/**
 * Declare support for HPOS (High-Performance Order Storage)
 */
add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}
);

/**
 * Whether notices must be displayed in the current page (plugins and WooCommerce pages).
 */
function leat_should_display_compatibility_notices()
{
	$current_screen = get_current_screen();

	if (! isset($current_screen)) {
		return false;
	}

	$is_plugins_page     =
		property_exists($current_screen, 'id') &&
		'plugins' === $current_screen->id;
	$is_woocommerce_page =
		property_exists($current_screen, 'parent_base') &&
		'woocommerce' === $current_screen->parent_base;

	return $is_plugins_page || $is_woocommerce_page;
}

if (version_compare($GLOBALS['wp_version'], $minimum_wp_version, '<')) {
	/**
	 * Outputs for an admin notice about running Leat on outdated WordPress.
	 */
	function leat_admin_unsupported_wp_notice()
	{
		if (leat_should_display_compatibility_notices()) {
?>
			<div class="notice notice-error">
				<p><?php esc_html_e('The Leat plugin requires a more recent version of WordPress and has been paused. Please update WordPress to continue enjoying Leat.', 'leat-crm'); ?></p>
			</div>
		<?php
		}
	}
	add_action('admin_notices', 'leat_admin_unsupported_wp_notice');
	return;
}

/**
 * Autoload packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';

if (is_readable($autoloader)) {
	require $autoloader;
} else {
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log(  // phpcs:ignore
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__('Your installation of the Leat plugin is incomplete. Please run %1$s within the %2$s directory.', 'leat-crm'),
				'`composer install`',
				'`' . esc_html(plugin_dir_path(__FILE__)) . '`'
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
					esc_html__('Your installation of the Leat plugin is incomplete. Please run %1$s within the %2$s directory.', 'leat-crm'),
					'<code>composer install</code>',
					'<code>' . esc_html(plugin_dir_path(__FILE__)) . '</code>'
				);
				?>
			</p>
		</div>
<?php
		}
	);
	return;
}

add_action('plugins_loaded', array('\Leat\Package', 'init'));
