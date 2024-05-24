<?php
namespace PiggyWP;

use PiggyWP\Options;
use PiggyWP\Assets\Api as AssetApi;
use Kucrut\Vite;

/**
 * AssetsController class.
 *
 * @since    5.0.0
 * @internal
 */
final class AssetsController {
	const APP_HANDLE       = 'piggy-frontend';
	const ADMIN_APP_HANDLE = 'piggy-admin';

	/**
	 * Asset API interface for various asset registration.
	 *
	 * @var AssetApi
	 */
	private $assets_api;

	/**
	 * Contains registered data.
	 *
	 * @var array
	 */
	private $wc_settings_data = array();

	/**
	 * Contains options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Slug of the plugin screen.
	 *
	 * @var string
	 */
	public $plugin_screen_hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param AssetApi $asset_api Asset API interface for various asset registration.
	 * @param Options  $options   Options interface.
	 */
	public function __construct( AssetApi $asset_api, Options $options ) {
		$this->assets_api     = $asset_api;
		$this->options = $options;
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'wp_footer', array( $this, 'render_frontend_mount' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ), 80 );
		add_action( 'wp_head', array( $this, 'enqueue_frontend_dynamic_css' ), 90 );
		add_action( 'wp_head', array( $this, 'enqueue_custom_css' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_js' ), 90 );

		$this->plugin_screen_hook_suffix = add_menu_page(
			'Piggy',
			'Piggy',
			'manage_options',
			'piggy',
			array( $this, 'render_admin_mount' ),
			'dashicons-cart',
			'99.999'
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ), 100 );
	}


	protected function get_middleware_config() {
		return "
			window.piggyMiddlewareConfig = {
				storeApiNonce: '" . esc_js( wp_create_nonce( 'wc_store_api' ) ) . "',
				wcStoreApiNonceTimestamp: '" . esc_js( time() ) . "'
			};
		";
	}

	/**
	 * Register assets.
	 */
	public function enqueue_frontend() {
		$this->assets_api->register_script(
			self::APP_HANDLE,
			'frontend',
			'ts/frontend/index.ts',
			array( 'wp-api-fetch', 'wp-i18n', 'wp-a11y', 'wp-keycodes', 'wp-html-entities' )
		);

		if ( wp_script_is( self::APP_HANDLE, 'enqueued' ) ) {
			$settings = rawurlencode( wp_json_encode( $this->options->get_frontend_options_payload() ) );

			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				"window.piggyConfig = JSON.parse( decodeURIComponent( '" . esc_js( $settings ) . "' ) );",
				'before'
			);
		}

		if ( wp_script_is( self::APP_HANDLE, 'enqueued' ) ) {
			$this->initialize_core_data();

			$wc_settings = rawurlencode( wp_json_encode( $this->wc_settings_data ) );

			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				"window.piggyWcSettings = JSON.parse( decodeURIComponent( '" . esc_js( $wc_settings ) . "' ) );",
				'before'
			);
		}

		if ( wp_script_is( self::APP_HANDLE, 'enqueued' ) ) {
			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				$this->get_middleware_config(),
				'before'
			);
		}

		// if ( wp_script_is( self::APP_HANDLE, 'enqueued' ) ) {
			wp_add_inline_style(
				self::APP_HANDLE,
				$this->get_dynamic_css()
			);
		// }
	}


	/**
	 * Enqueue custom CSS.
	 */
	public function enqueue_custom_css() {
		echo '<style>' . esc_html( wp_unslash( $this->get_custom_css() ) ) . '</style>';
	}

	/**
	 * Enqueue custom JS.
	 */
	public function enqueue_custom_js() {
		$custom_js = $this->get_custom_js();

		if ( $custom_js ) {
			$this->assets_api->add_inline_script( self::APP_HANDLE, wp_unslash( $custom_js ) );
		}
	}

	/**
	 * Enqueue dynamic CSS.
	 */
	public function enqueue_frontend_dynamic_css() {
		echo '<style id="piggy-dynamic-css">' . esc_html( $this->get_dynamic_css() ) . '</style>';
	}

	/**
	 * The app mount.
	 */
	public function render_frontend_mount() {
		echo '<div id="piggy-frontend-mount"></div>';
	}

	/**
	 * Register assets.
	 */
	public function enqueue_admin() {
		$screen = get_current_screen();

		if ( $this->plugin_screen_hook_suffix !== $screen->id ) {
			return;
		}

		$this->assets_api->register_script(
			self::ADMIN_APP_HANDLE,
			'admin',
			'ts/admin/index.ts',
			array( 'wp-api-fetch', 'wp-i18n', 'wp-a11y', 'wp-keycodes', 'wp-hooks' )
		);

		if ( wp_script_is( self::ADMIN_APP_HANDLE, 'enqueued' ) ) {
			$settings = rawurlencode( wp_json_encode( $this->options->get_admin_options_payload() ) );

			$this->assets_api->add_inline_script(
				self::ADMIN_APP_HANDLE,
				// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				"window.piggySettingsConfig = JSON.parse( decodeURIComponent( '" . esc_js( $settings ) . "' ) );",
				'before'
			);
		}

		if ( wp_script_is( self::ADMIN_APP_HANDLE, 'enqueued' ) ) {
			$this->initialize_core_data();

			$wc_settings = rawurlencode( wp_json_encode( $this->wc_settings_data ) );

			$this->assets_api->add_inline_script(
				self::ADMIN_APP_HANDLE,
				"window.piggyWcSettings = JSON.parse( decodeURIComponent( '" . esc_js( $wc_settings ) . "' ) );",
				'before'
			);
		}

		if ( wp_script_is( self::ADMIN_APP_HANDLE, 'enqueued' ) ) {
			$this->assets_api->add_inline_script(
				self::ADMIN_APP_HANDLE,
				$this->get_middleware_config(),
				'before'
			);
		}
	}

	/**
	 * The app mount.
	 */
	public function render_admin_mount() {
		?>
			<div id="piggy-admin-mount"></div>
		<?php
	}

	/**
	 * Exposes core data via the piggyWcSettings global. This data is shared throughout the client.
	 *
	 * Settings that are used by various components or multiple blocks should be added here. Note, that settings here are
	 * global so be sure not to add anything heavy if possible.
	 *
	 * @return array  An array containing core data.
	 */
	protected function get_wc_settings() {
		return array(
			'adminUrl'                  => admin_url(),
			'countries'                 => WC()->countries->get_countries(),
			'countryTaxOrVat'           => WC()->countries->tax_or_vat(),
			'currency'                  => $this->get_currency_data(),
			'currentUserIsAdmin'        => current_user_can( 'manage_woocommerce' ),
			'homeUrl'                   => esc_url( home_url( '/' ) ),
			'locale'                    => $this->get_locale_data(),
			'placeholderImgSrc'         => wc_placeholder_img_src(),
			'taxesEnabled'              => wc_tax_enabled(),
			'couponsEnabled'            => wc_coupons_enabled(),
			'displayCartPricesIncludingTax' => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			'shippingEnabled'           => wc_shipping_enabled(),
			'baseLocation'              => wc_get_base_location(),
			'shippingCalculatorEnabled' => filter_var( get_option( 'woocommerce_enable_shipping_calc' ), FILTER_VALIDATE_BOOLEAN ),
			'showCartPricesIncTax'      => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			'taxTotalDisplayItemized'   => 'itemized' === get_option( 'woocommerce_tax_total_display' ),
			'productsSettings'          => $this->get_products_settings(),
			'siteTitle'                 => get_bloginfo( 'name' ),
			'storePages'                => $this->get_store_pages(),
			'wcAssetUrl'                => plugins_url( 'assets/', WC_PLUGIN_FILE ),
			'wcVersion'                 => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'wpLoginUrl'                => wp_login_url(),
			'wpVersion'                 => get_bloginfo( 'version' ),
			'endpoints'                 => array(
				'order-received' => array(
					'active' => is_wc_endpoint_url( 'order-received' ),
				),
			),
		);
	}

	/**
	 * Used for on demand initialization of asset data and registering it with
	 * the internal data registry.
	 */
	protected function initialize_core_data() {
		$this->wc_settings_data = $this->get_wc_settings();
	}

	/**
	 * Get currency data to include in settings.
	 *
	 * @return array
	 */
	protected function get_currency_data() {
		$currency = get_woocommerce_currency();

		return array(
			'code'              => $currency,
			'precision'         => wc_get_price_decimals(),
			'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
			'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
			'decimalSeparator'  => wc_get_price_decimal_separator(),
			'thousandSeparator' => wc_get_price_thousand_separator(),
			'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
		);
	}

	/**
	 * Get locale data to include in settings.
	 *
	 * @return array
	 */
	protected function get_locale_data() {
		global $wp_locale;

		return array(
			'siteLocale'    => get_locale(),
			'userLocale'    => get_user_locale(),
			'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
		);
	}

	/**
	 * Get store pages to include in settings.
	 *
	 * @return array
	 */
	protected function get_store_pages() {
		return array_map(
			array( $this, 'format_page_resource' ),
			array(
				'myaccount' => wc_get_page_id( 'myaccount' ),
				'shop'      => wc_get_page_id( 'shop' ),
				'cart'      => wc_get_page_id( 'cart' ),
				'checkout'  => wc_get_page_id( 'checkout' ),
				'privacy'   => wc_privacy_policy_page_id(),
				'terms'     => wc_terms_and_conditions_page_id(),
			)
		);
	}

	/**
	 * Get product related settings.
	 *
	 * Note: For the time being we are exposing only the settings that are used by blocks.
	 *
	 * @return array
	 */
	protected function get_products_settings() {
		return array(
			'cartRedirectAfterAdd' => get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes',
		);
	}

	/**
	 * Get custom JS for output.
	 */
	protected function get_custom_js() {
		$custom_js = $this->options->get( 'custom_js' );

		if ( ! empty( $custom_js ) ) {
			return $custom_js;
		}
		return '';
	}

	/**
	 * Get custom CSS for output.
	 */
	protected function get_custom_css() {
		$custom_css = $this->options->get( 'custom_css' );

		if ( ! empty( $custom_css ) ) {
			return $custom_css;
		}
		return '';
	}

	/**
	 * Get dynamic CSS output.
	 */
	protected function get_dynamic_css() {
		$options = $this->options->get_frontend_options_payload();

		if ( empty( $options ) ) {
			return '';
		}

		$css = ':root {';

		// Gather colors.
		$colors = array_filter(
			$options,
			function( $key ) {
				return strpos( $key, 'color_' ) === 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		foreach ( $colors as $key => $value ) {
			$css .= '--piggy-color-' . str_replace( '_', '-', substr( $key, 6 ) ) . ':' . $value . ';';
		}

		// Misc.
		$border_radius       = $this->options->get( 'border_radius_rounded' ) === 'on' ? '6px' : '0';
		$drawer_width        = $this->options->get( 'customize_width_drawer_desktop' ) . 'px';
		$drawer_width_mobile = $this->options->get( 'customize_width_drawer_mobile' ) . '%';
		$white_space         = $this->options->get( 'customize_white_space_text' );
		$animation_duration  = $this->options->get( 'customize_animation_duration' ) . 'ms';

		$css .= '
			--piggy-width-drawer-desktop: ' . $drawer_width . ';
			--piggy-width-drawer-mobile: ' . $drawer_width_mobile . ';
			--piggy-white-space-text: ' . $white_space . ';
			--piggy-animation-duration: ' . $animation_duration . ';
			--piggy-border-radius: ' . $border_radius . ';
			--piggy-px-drawer-desktop: 25px;
			--piggy-px-drawer-mobile: 4px;
			--piggy-button-hover-opacity: 0.7;
		';

		$css .= '}';

		return $css;
	}

	/**
	 * Format a page object into a standard array of data.
	 *
	 * @param WP_Post|int $page Page object or ID.
	 * @return array
	 */
	protected function format_page_resource( $page ) {
		if ( is_numeric( $page ) && $page > 0 ) {
			$page = get_post( $page );
		}

		if ( ! is_a( $page, '\WP_Post' ) || 'publish' !== $page->post_status ) {
			return array(
				'id'        => 0,
				'title'     => '',
				'permalink' => false,
			);
		}

		return array(
			'id'        => $page->ID,
			'title'     => $page->post_title,
			'permalink' => get_permalink( $page->ID ),
		);
	}
}
