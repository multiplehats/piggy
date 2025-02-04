<?php

/**
 * Vite integration for WordPress
 *
 * Forked from: https://github.com/kucrut/vite-for-wp
 * Version: v0.10.0  (Dec 18, 2024)
 *
 * Changes:
 *
 * Feb 4, 2025
 * - Converted all functions to static methods within the Vite class
 * - Changed the constant to be private class constant
 * - Moved static variables into the class
 * - Made helper methods private
 * - Made public only the methods that need to be accessed from outside (register_asset and enqueue_asset)
 * - Updated all function calls to use self:: to reference class methods
 * - Extracted the recursive import checking into its own method
 * - Added trip URL parameters for comparison to the set_script_type_attribute method to avoid conflicts iwth caching plugins
 *
 * @package Leat\Assets
 */

declare(strict_types=1);

namespace Leat\Assets;

use Exception;
use WP_HTML_Tag_Processor;

class Vite
{
	private const VITE_CLIENT_SCRIPT_HANDLE = 'vite-client';
	private static $manifests = [];
	private static $is_react_refresh_preamble_printed = false;

	/**
	 * Get manifest data
	 *
	 * @since 0.1.0
	 * @since 0.8.0 Use wp_json_file_decode().
	 *
	 * @param string $manifest_dir Path to manifest directory.
	 *
	 * @throws Exception Exception is thrown when the file doesn't exist, unreadble, or contains invalid data.
	 *
	 * @return object Object containing manifest type and data.
	 */
	private static function get_manifest(string $manifest_dir): object
	{
		$dev_manifest = 'vite-dev-server';
		$file_names = [$dev_manifest, 'manifest'];

		foreach ($file_names as $file_name) {
			$is_dev = $file_name === $dev_manifest;
			$manifest_path = "{$manifest_dir}/{$file_name}.json";

			if (isset(self::$manifests[$manifest_path])) {
				return self::$manifests[$manifest_path];
			}

			if (is_file($manifest_path) && is_readable($manifest_path)) {
				break;
			}

			unset($manifest_path);
		}

		if (! isset($manifest_path)) {
			throw new Exception(esc_html(sprintf('[Vite] No manifest found in %s.', $manifest_dir)));
		}

		$manifest = wp_json_file_decode($manifest_path);

		if (! $manifest) {
			throw new Exception(esc_html(sprintf('[Vite] Failed to read manifest file %s.', $manifest_path)));
		}

		/**
		 * Filter manifest data
		 *
		 * @param array  $manifest      Manifest data.
		 * @param string $manifest_dir  Manifest directory path.
		 * @param string $manifest_path Manifest file path.
		 * @param bool   $is_dev        Whether this is a manifest for development assets.
		 */
		$manifest = apply_filters('vite_for_wp__manifest_data', $manifest, $manifest_dir, $manifest_path);

		self::$manifests[$manifest_path] = (object) [
			'data' => $manifest,
			'dir' => $manifest_dir,
			'is_dev' => $is_dev,
		];

		return self::$manifests[$manifest_path];
	}

	/**
	 * Filter script tag
	 *
	 * This creates a function to be used as callback for the `script_loader` filter
	 * which adds `type="module"` attribute to the script tag.
	 *
	 * @since 0.1.0
	 *
	 * @param string $handle Script handle.
	 *
	 * @return void
	 */
	private static function filter_script_tag(string $handle): void
	{
		add_filter('script_loader_tag', fn(...$args) => self::set_script_type_attribute($handle, ...$args), 10, 3);
	}

	/**
	 * Add `type="module"` to a script tag
	 *
	 * @since 0.1.0
	 * @since 0.8.0 Use WP_HTML_Tag_Processor.
	 *
	 * @param string $target_handle Handle of the script being targeted by the filter callback.
	 * @param string $tag           Original script tag.
	 * @param string $handle        Handle of the script that's currently being filtered.
	 * @param string $src           The sript src.
	 *
	 * @return string Script tag with attribute `type="module"` added.
	 */
	private static function set_script_type_attribute(string $target_handle, string $tag, string $handle, string $src): string
	{
		if ($target_handle !== $handle) {
			return $tag;
		}

		$processor = new WP_HTML_Tag_Processor($tag);

		// Strip URL parameters for comparison
		$clean_src = preg_replace('/\?.*$/', '', $src);

		while ($processor->next_tag('script')) {
			$current_src = $processor->get_attribute('src');
			if ($current_src) {
				// Strip URL parameters for comparison
				$clean_current_src = preg_replace('/\?.*$/', '', $current_src);

				if ($clean_current_src === $clean_src) {
					$processor->set_attribute('type', 'module');
					break;
				}
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Generate development asset src
	 *
	 * @since 0.1.0
	 *
	 * @param object $manifest Asset manifest.
	 * @param string $entry    Asset entry name.
	 *
	 * @return string
	 */
	private static function generate_development_asset_src(object $manifest, string $entry): string
	{
		return sprintf(
			'%s/%s',
			untrailingslashit($manifest->data->origin),
			trim(preg_replace('/[\/]{2,}/', '/', "{$manifest->data->base}/{$entry}"), '/')
		);
	}

	/**
	 * Register vite client script
	 *
	 * @since 0.1.0
	 *
	 * @param object $manifest Asset manifest.
	 *
	 * @return void
	 */
	private static function register_vite_client_script(object $manifest): void
	{
		if (wp_script_is(self::VITE_CLIENT_SCRIPT_HANDLE)) {
			return;
		}

		$src = self::generate_development_asset_src($manifest, '@vite/client');

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script(self::VITE_CLIENT_SCRIPT_HANDLE, $src, [], null, false);
		self::filter_script_tag(self::VITE_CLIENT_SCRIPT_HANDLE);
	}

	/**
	 * Inject react-refresh preamble script once, if needed
	 *
	 * @since 0.8.0
	 *
	 * @param object $manifest Asset manifest.
	 * @return void
	 */
	private static function inject_react_refresh_preamble_script(object $manifest): void
	{
		if (self::$is_react_refresh_preamble_printed) {
			return;
		}

		if (! in_array('vite:react-refresh', $manifest->data->plugins, true)) {
			return;
		}

		$react_refresh_script_src = self::generate_development_asset_src($manifest, '@react-refresh');
		$script_position = 'after';
		$script = <<< EOS
import RefreshRuntime from "{$react_refresh_script_src}";
RefreshRuntime.injectIntoGlobalHook(window);
window.\$RefreshReg$ = () => {};
window.\$RefreshSig$ = () => (type) => type;
window.__vite_plugin_react_preamble_installed__ = true;
EOS;

		wp_add_inline_script(self::VITE_CLIENT_SCRIPT_HANDLE, $script, $script_position);
		add_filter(
			'wp_inline_script_attributes',
			function (array $attributes) use ($script_position): array {
				if (isset($attributes['id']) && $attributes['id'] === self::VITE_CLIENT_SCRIPT_HANDLE . "-js-{$script_position}") {
					$attributes['type'] = 'module';
				}

				return $attributes;
			}
		);

		self::$is_react_refresh_preamble_printed = true;
	}

	/**
	 * Load development asset
	 *
	 * @since 0.1.0
	 *
	 * @param object $manifest Asset manifest.
	 * @param string $entry    Entrypoint to enqueue.
	 * @param array  $options  Enqueue options.
	 *
	 * @return array|null Array containing registered scripts or NULL if the none was registered.
	 */
	private static function load_development_asset(object $manifest, string $entry, array $options): ?array
	{
		self::register_vite_client_script($manifest);
		self::inject_react_refresh_preamble_script($manifest);

		$dependencies = array_merge(
			[self::VITE_CLIENT_SCRIPT_HANDLE],
			$options['dependencies']
		);

		$src = self::generate_development_asset_src($manifest, $entry);

		self::filter_script_tag($options['handle']);

		// This is a development script, browsers shouldn't cache it.
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		if (! wp_register_script($options['handle'], $src, $dependencies, null, $options['in-footer'])) {
			return null;
		}

		$assets = [
			'scripts' => [$options['handle']],
			'styles' => $options['css-dependencies'],
		];

		/**
		 * Filter registered development assets
		 *
		 * @param array  $assets   Registered assets.
		 * @param object $manifest Manifest object.
		 * @param string $entry    Entrypoint file.
		 * @param array  $options  Enqueue options.
		 */
		$assets = apply_filters('vite_for_wp__development_assets', $assets, $manifest, $entry, $options);

		return $assets;
	}

	/**
	 * Load production asset
	 *
	 * @since 0.1.0
	 *
	 * @param object $manifest Asset manifest.
	 * @param string $entry    Entrypoint to enqueue.
	 * @param array  $options  Enqueue options.
	 *
	 * @return array|null Array containing registered scripts & styles or NULL if there was an error.
	 */
	private static function load_production_asset(object $manifest, string $entry, array $options): ?array
	{
		$url = self::prepare_asset_url($manifest->dir);

		if (! isset($manifest->data->{$entry})) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				wp_die(esc_html(sprintf('[Vite] Entry %s not found.', $entry)));
			}

			return null;
		}

		$assets = [
			'scripts' => [],
			'styles' => [],
		];
		$item = $manifest->data->{$entry};
		$src = "{$url}/{$item->file}";

		if (! $options['css-only']) {
			self::filter_script_tag($options['handle']);

			// Don't worry about browser caching as the version is embedded in the file name.
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			if (wp_register_script($options['handle'], $src, $options['dependencies'], null, $options['in-footer'])) {
				$assets['scripts'][] = $options['handle'];
			}
		}

		if (! empty($item->imports)) {
			// Recursive inline function to deeply check for .css files.
			self::check_imports($item->imports, $assets, $manifest, $url, $options);
		}

		if (! empty($item->css)) {
			self::register_stylesheets($assets, $item->css, $url, $options);
		}

		/**
		 * Filter registered production assets
		 *
		 * @param array  $assets   Registered assets.
		 * @param object $manifest Manifest object.
		 * @param string $entry    Entrypoint file.
		 * @param array  $options  Enqueue options.
		 */
		$assets = apply_filters('vite_for_wp__production_assets', $assets, $manifest, $entry, $options);

		return $assets;
	}

	private static function check_imports(array $imports, array &$assets, object $manifest, string $url, array $options): void
	{
		foreach ($imports as $import) {
			$import_item = $manifest->data->{$import};

			if (! empty($import_item->imports)) {
				self::check_imports($import_item->imports, $assets, $manifest, $url, $options);
			}

			if (! empty($import_item->css)) {
				self::register_stylesheets($assets, $import_item->css, $url, $options);
			}
		}
	}

	/**
	 * Register stylesheet assets to WordPress and saves stylesheet handles for later enqueuing
	 *
	 * @param array  $assets      Reference to registered assets.
	 * @param array  $stylesheets List of stylesheets to register.
	 * @param string $url         Base URL to asset.
	 * @param array  $options     Array of options.
	 */
	private static function register_stylesheets(array &$assets, array $stylesheets, string $url, array $options): void
	{
		foreach ($stylesheets as $css_file_path) {
			$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', pathinfo($css_file_path, PATHINFO_FILENAME)), '-'));
			// Including a slug based on the actual css file in the handle ensures it wont be registered more than once.
			$style_handle = "{$options['handle']}-{$slug}";

			// Don't worry about browser caching as the version is embedded in the file name.
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			if (wp_register_style($style_handle, "{$url}/{$css_file_path}", $options['css-dependencies'], null, $options['css-media'])) {
				$assets['styles'][] = $style_handle;
			}
		}
	}

	/**
	 * Parse register/enqueue options
	 *
	 * @since 0.1.0
	 *
	 * @param array $options Array of options.
	 *
	 * @return array Array of options merged with defaults.
	 */
	private static function parse_options(array $options): array
	{
		$defaults = [
			'css-dependencies' => [],
			'css-media' => 'all',
			'css-only' => false,
			'dependencies' => [],
			'handle' => '',
			'in-footer' => false,
		];

		return wp_parse_args($options, $defaults);
	}

	/**
	 * Prepare asset url
	 *
	 * @author Justin Slamka <jslamka5685@gmail.com>
	 * @since 0.4.0
	 * @since 0.6.1 Normalize paths so they work on Windows as well.
	 *
	 * @param string $dir Asset directory.
	 *
	 * @return string
	 */
	private static function prepare_asset_url(string $dir)
	{
		$content_dir = wp_normalize_path(WP_CONTENT_DIR);
		$manifest_dir = wp_normalize_path($dir);
		$url = content_url(str_replace($content_dir, '', $manifest_dir));
		$url_matches_pattern = preg_match('/(?<address>http(?:s?):\/\/.*\/)(?<fullPath>wp-content(?<removablePath>\/.*)\/(?:plugins|themes)\/.*)/', $url, $url_parts);

		if ($url_matches_pattern === 0) {
			return $url;
		}

		['address' => $address, 'fullPath' => $full_path, 'removablePath' => $removable_path] = $url_parts;

		return sprintf('%s%s', $address, str_replace($removable_path, '', $full_path));
	}

	/**
	 * Register asset
	 *
	 * @since 0.1.0
	 *
	 * @see load_development_asset
	 * @see load_production_asset
	 *
	 * @param string $manifest_dir Path to directory containing manifest file, usually `build` or `dist`.
	 * @param string $entry        Entrypoint to enqueue.
	 * @param array  $options      Enqueue options.
	 *
	 * @return array
	 */
	public static function register_asset(string $manifest_dir, string $entry, array $options): ?array
	{
		try {
			$manifest = self::get_manifest($manifest_dir);
		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				wp_die(esc_html($e->getMessage()));
			}

			return null;
		}

		$options = self::parse_options($options);
		$assets = $manifest->is_dev
			? self::load_development_asset($manifest, $entry, $options)
			: self::load_production_asset($manifest, $entry, $options);

		return $assets;
	}

	/**
	 * Enqueue asset
	 *
	 * @since 0.1.0
	 *
	 * @see register_asset
	 *
	 * @param string $manifest_dir Path to directory containing manifest file, usually `build` or `dist`.
	 * @param string $entry        Entrypoint to enqueue.
	 * @param array  $options      Enqueue options.
	 *
	 * @return bool
	 */
	public static function enqueue_asset(string $manifest_dir, string $entry, array $options): bool
	{
		$assets = self::register_asset($manifest_dir, $entry, $options);

		if (is_null($assets)) {
			return false;
		}

		$map = [
			'scripts' => 'wp_enqueue_script',
			'styles' => 'wp_enqueue_style',
		];

		foreach ($assets as $group => $handles) {
			$func = $map[$group];

			foreach ($handles as $handle) {
				$func($handle);
			}
		}

		return true;
	}
}
