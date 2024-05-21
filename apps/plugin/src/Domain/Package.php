<?php

namespace Piggy\Domain;

use Piggy\Options;
use Automattic\WooCommerce\Blocks\Domain\Services\FeatureGating;

/**
 * Main package class.
 *
 * Returns information about the package and handles init.
 *
 * @since 2.5.0
 */
class Package {
	/**
	 * Holds the current version of the Piggy plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Holds the main path to the Piggy plugin directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Holds locally the plugin_dir_url to avoid recomputing it.
	 *
	 * @var string
	 */
	private $plugin_dir_url;

	/**
	 * Constructor
	 *
	 * @param string $version        Version of the plugin.
	 * @param string $plugin_path    Path to the main plugin file.
	 */
	public function __construct( $version, $plugin_path ) {
		$this->version = $version;
		$this->path    = $plugin_path;
	}

	/**
	 * Returns the version of the plugin.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Returns the version of the plugin stored in the database.
	 *
	 * @return string
	 */
	public function get_version_stored_on_db() {
		return Options::get( Options::PIGGY_VERSION );
	}

	/**
	 * Set the version of the plugin stored in the database.
	 * This is useful during the first installation or after the upgrade process.
	 */
	public function set_version_stored_on_db() {
		update_option( Options::PIGGY_VERSION, $this->get_version() );
	}

	/**
	 * Returns the path to the plugin directory.
	 *
	 * @param string $relative_path  If provided, the relative path will be
	 *                               appended to the plugin path.
	 *
	 * @return string
	 */
	public function get_path( $relative_path = '' ) {
		return trailingslashit( $this->path ) . $relative_path;
	}

	/**
	 * Get this plugin's directory path, relative to this file's location.
	 *
	 * This file should be in `/src` and we want one level above.
	 * Example: /app/public/wp-content/plugins/piggy/
	 *
	 * @return string
	 */
	public function plugin_dir_path(): string {
		return trailingslashit( realpath( __DIR__ . DIRECTORY_SEPARATOR . '../..' ) );
	}

	/**
	 * Returns the url to the Piggy plugin directory.
	 *
	 * @param string $relative_url If provided, the relative url will be
	 *                             appended to the plugin url.
	 *
	 * @return string
	 */
	public function get_url( $relative_url = '' ) {
		if ( ! $this->plugin_dir_url ) {
			// Append index.php so WP does not return the parent directory.
			$this->plugin_dir_url = plugin_dir_url( $this->path . '/index.php' );
		}

		return $this->plugin_dir_url . $relative_url;
	}
}
