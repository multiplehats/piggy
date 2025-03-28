<?php

namespace Leat\Assets;

use Leat\Domain\Package;
use Exception;
use Leat\Assets\Vite;

/**
 * The Api class provides an interface to various asset registration helpers.
 *
 * Contains asset api methods
 */
class Api
{
	/**
	 * Stores inline scripts already enqueued.
	 *
	 * @var array
	 */
	private $inline_scripts = [];

	/**
	 * Reference to the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Constructor for class
	 *
	 * @param Package $package An instance of Package.
	 */
	public function __construct(Package $package)
	{
		$this->package = $package;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file (relative to the plugin
	 *                     directory).
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version($file)
	{
		if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($this->package->get_path() . $file)) {
			return filemtime($this->package->get_path(trim($file, '/')));
		}
		return $this->package->get_version();
	}

	/**
	 * Retrieve the url to an asset for this plugin.
	 *
	 * @param string $relative_path An optional relative path appended to the
	 *                              returned url.
	 *
	 * @return string
	 */
	protected function get_asset_url($relative_path = '')
	{
		return $this->package->get_url($relative_path);
	}

	/**
	 * Get the path to a block's metadata
	 *
	 * @param string $block_name The block to get metadata for.
	 *
	 * @return string|boolean False if metadata file is not found for the block.
	 */
	public function get_block_metadata_path($block_name)
	{
		$path_to_metadata_from_plugin_root = $this->package->get_path('dist/' . $block_name . '/block.json');
		if (! file_exists($path_to_metadata_from_plugin_root)) {
			return false;
		}
		return $path_to_metadata_from_plugin_root;
	}

	/**
	 * Get src, version and dependencies given a script relative src.
	 *
	 * @param string $relative_src Relative src to the script.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 *
	 * @return array src, version and dependencies of the script.
	 */
	public function get_script_data($relative_src, $dependencies = [])
	{
		$src     = '';
		$version = '1';

		if ($relative_src) {
			$src        = $this->get_asset_url($relative_src);
			$asset_path = $this->package->get_path(
				str_replace('.js', '.asset.php', $relative_src)
			);

			if (file_exists($asset_path)) {
				$asset        = require $asset_path;
				$dependencies = isset($asset['dependencies']) ? array_merge($asset['dependencies'], $dependencies) : $dependencies;
				$version      = ! empty($asset['version']) ? $asset['version'] : $this->get_file_version($relative_src);
			} else {
				$version = $this->get_file_version($relative_src);
			}
		}

		return array(
			'src'          => $src,
			'version'      => $version,
			'dependencies' => $dependencies,
		);
	}

	/**
	 * Registers a script according to `wp_register_script`, adding the correct prefix, and additionally loading translations.
	 *
	 * @throws Exception If the registered script has a dependency on itself.
	 *
	 * @param string $handle        Unique name of the script.
	 * @param string $relative_entry  Relative url for the entry (e.g. ts/frontend/index.ts).
	 * @param string $folder  Folder for asset path.
	 * @param array  $dependencies  Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param bool   $has_i18n      Optional. Whether to add a script translation call to this file. Default: true.
	 */
	public function register_script($handle, $relative_entry, $folder = 'frontend', $dependencies = [], $has_i18n = true)
	{
		/**
		 * Filters the list of script dependencies.
		 *
		 * @param array $dependencies The list of script dependencies.
		 * @param string $handle The script's handle.
		 * @return array
		 */
		$script_dependencies = apply_filters('leat_register_script_dependencies', $dependencies, $handle);

		Vite::enqueue_asset(
			$this->get_manifest_path($folder),
			$relative_entry,
			array(
				'dependencies' => $script_dependencies,
				'handle'       => $handle,
				'in-footer'    => true,
			)
		);

		if ($has_i18n && function_exists('wp_set_script_translations')) {
			wp_set_script_translations($handle, 'leat-crm', $this->package->get_path('languages'));
		}
	}

	/**
	 * Gets the path to the manifest.
	 *
	 * @param string $folder The folder to look for the manifest in.
	 * @throws \Exception If the manifest file is not found.
	 *
	 * @return string The path to the manifest.
	 */
	public function get_manifest_path($folder = 'frontend')
	{
		$manifest = $this->package->get_path("dist/$folder");

		if (! file_exists($manifest)) {
			throw new \Exception(
				sprintf(
					'Manifest path not found: %s',
					esc_html($manifest)
				),
				500
			);
		}

		return $manifest;
	}

	/**
	 * Get the assets URL to the build folder.
	 *
	 * @param string $folder The folder in which to look.
	 */
	public function get_manifest_path_url(string $folder = 'frontend')
	{
		return $this->package->get_url() . "dist/$folder/";
	}

	/**
	 * Registers a style according to `wp_register_style`.
	 *
	 * @param string $handle       Name of the stylesheet. Should be unique.
	 * @param string $relative_src Relative source of the stylesheet to the plugin path.
	 * @param array  $deps         Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media        Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
	 *                             'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public function register_style($handle, $relative_src, $deps = [], $media = 'all')
	{
		$filename = str_replace(plugins_url('/', __DIR__), '', $relative_src);
		$src      = $this->get_asset_url($relative_src);
		$ver      = $this->get_file_version($filename);
		wp_register_style($handle, $src, $deps, $ver, $media);
	}

	/**
	 * Returns the appropriate asset path for loading current builds.
	 *
	 * @param   string $folder  Folder for asset path.
	 * @return  string             The generated path.
	 */
	public function get_dist_path($folder = 'frontend')
	{
		return "dist/$folder/";
	}
}
