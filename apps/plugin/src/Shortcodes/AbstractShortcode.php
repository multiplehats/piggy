<?php
namespace Leat\Shortcodes;

use Leat\Assets\Api as AssetApi;

/**
 * AbstractShortcode class.
 */
abstract class AbstractShortcode {
	/**
	 * Shortcode namespaces.
	 *
	 * @var array
	 */
	protected $namespaces = ['leat', 'piggy'];

	/**
	 * Shortcode name within these namespaces.
	 *
	 * @var string
	 */
	protected $shortcode_name = '';

	/**
	 * Tracks if assets have been enqueued.
	 *
	 * @var boolean
	 */
	protected $enqueued_assets = false;

	/**
	 * Instance of the asset API.
	 *
	 * @var AssetApi
	 */
	protected $asset_api;

	/**
	 * Constructor.
	 *
	 * @param AssetApi            $asset_api Instance of the asset API.
	 * @param AssetDataRegistry   $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetApi $asset_api ) {
		$this->asset_api            = $asset_api;
	}

	/**
	 * Initialize this shortcode type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the shortcode with WordPress.
	 */
	public function init() {
		if ( empty( $this->shortcode_name ) ) {
			throw new \Exception(esc_html__('Shortcode name is required.', 'leat'));
		}

		$this->init_hooks();
		$this->register_shortcode_type();
	}

	/**
	 * Initialize hooks.
	 */
	abstract protected function init_hooks();

	/**
	 * Registers the shortcode type with WordPress.
	 */
	protected function register_shortcode_type() {
		foreach ($this->namespaces as $namespace) {
			$shortcode_type = $namespace . '_' . $this->shortcode_name;
			if (!shortcode_exists($shortcode_type)) {
				add_shortcode($shortcode_type, [$this, 'render_callback']);
			}
		}
	}

	/**
	 * Get shortcode default attributes.
	 * Child classes must implement this method to define default attributes.
	 *
	 * @return array Default attributes for the shortcode.
	 */
	abstract protected function get_shortcode_type_attributes();

	/**
	 * The callback function to render the shortcode.
	 *
	 * @param array  $attributes The shortcode attributes.
	 * @param string $content The shortcode content.
	 * @return string The rendered shortcode content.
	 */
	public function render_callback($attributes, string $content = '') {
		// If admin, return empty string
		if ( is_admin() ){
			return '';
		}

		$attributes = (array) $attributes;

		// Ensure assets are enqueued when shortcode is used
		$this->enqueue_assets();

		// Merge user-defined attributes with defaults
		$attributes = shortcode_atts( $this->get_shortcode_type_attributes(), $attributes );

		// Generate the output
		$output = $this->shortcode_output($attributes, $content);

		return $output;
	}

	/**
	 * Generate the shortcode output.
	 *
	 * @param array  $attributes The shortcode attributes.
	 * @param string $content The shortcode content.
	 * @return string The rendered shortcode content.
	 */
	abstract protected function shortcode_output($attributes, $content = '');

	/**
	 * Get the shortcode types.
	 *
	 * @return array
	 */
	protected function get_shortcode_types() {
		return array_map(function($namespace) {
			return $namespace . '_' . $this->shortcode_name;
		}, $this->namespaces);
	}

	/**
	 * Determine if the current post has the shortcode.
	 *
	 * @param string $content The content to search in.
	 * @return bool
	 */
	protected function has_shortcode(string $content) {
		foreach ($this->get_shortcode_types() as $shortcode_type) {
			if (has_shortcode($content, $shortcode_type)) {
				return true;
			}
		}
		return false;
	}

	abstract protected function get_assets();

	public function enqueue_assets() {
		$assets = $this->get_assets();

		if ( empty( $assets ) ) {
			return;
		}

		foreach ($assets as $asset) {
				$this->register_script($asset);
		}
	}

	protected function register_script( array $asset ) {
		$this->asset_api->register_script(
			$asset['handle'],
			$asset['src'],
			$asset['dependencies'],
		);
	}
}
