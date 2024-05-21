<?php
namespace Piggy;

use Piggy\Options;
use Piggy\Assets\Api as AssetApi;
use Piggy\Utils\Customizer as Customizer;

/**
 * CustomizerController class.
 *
 * @since    5.0.0
 * @internal
 */
final class CustomizerController {
	/**
	 * Asset API interface for various asset registration.
	 *
	 * @var AssetApi
	 */
	private $api;

	/**
	 * Contains options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param AssetApi $asset_api Asset API interface for various asset registration.
	 */
	public function __construct( AssetApi $asset_api, Options $options ) {
		$this->api     = $asset_api;
		$this->options = $options;
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register customizer settings.
	 */
	public function register() {
		$panel = ( new Customizer() )->panel( __( 'My Panel', 'piggy' ) );

		$panel->register();
	}

	/**
	 * This will generate a line of CSS for use in header output. If the setting
	 * ($mod_name) has no defined value, the CSS will not be output.
	 *
	 * @uses get_theme_mod()
	 * @param string $selector CSS selector to be used in the output.
	 * @param string $style The name of the CSS *property* to modify (e.g. background-color).
	 * @param string $mod_name The name of the 'theme_mod' option to fetch the value from.
	 * @param string $prefix Optional. Anything that needs to be output before the CSS property.
	 * @param string $postfix Optional. Anything that needs to be output after the CSS property.
	 * @param bool   $echo Optional. Whether to print directly to the page (default: true).
	 * @return string Returns a single line of CSS with selectors and a property.
	 */
	public function generate_css( $selector, $style, $mod_name, $prefix = '', $postfix = '', $echo = true ) {
		$return = '';
		$mod    = get_theme_mod( $mod_name );
		if ( ! empty( $mod ) ) {
			$return = sprintf(
				'%s { %s:%s; }',
				$selector,
				$style,
				$prefix . $mod . $postfix
			);
			if ( $echo ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $return;
			}
		}
		return $return;
	}
}
