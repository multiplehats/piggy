<?php

namespace Leat;

use Leat\Api\Connection;
use Leat\Settings;
use Leat\Assets\Api as AssetApi;
use Leat\Utils\Common;
use Leat\Utils\Logger;
use WP_Post;

/**
 * AssetsController class.
 *
 * @since 5.0.0
 * @internal
 */
final class AssetsController {

	const APP_HANDLE       = 'leat-frontend';
	const ADMIN_APP_HANDLE = 'leat-admin';

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
	private $wc_settings_data = [];

	/**
	 * Contains Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Leat API connection.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * Slug of the plugin screen.
	 *
	 * @var string
	 */
	public $plugin_screen_hook_suffix = '';

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param AssetApi   $asset_api Asset API interface for various asset registration.
	 * @param Settings   $settings Settings interface.
	 * @param Connection $connection Leat API connection.
	 */
	public function __construct( AssetApi $asset_api, Settings $settings, Connection $connection ) {
		$this->assets_api = $asset_api;
		$this->settings   = $settings;
		$this->connection = $connection;
		$this->init();
		$this->logger = new Logger();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ], 80 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ], 100 );

		// phpcs:ignore -- this is a base64 encoded SVG icon for the WP admin menu.
		$icon_svg = 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( plugin_dir_path( __FILE__ ) . 'leat-wp-icon.svg' ) );

		$this->plugin_screen_hook_suffix = add_menu_page(
			'Leat',
			'Leat',
			'manage_options',
			'leat',
			[ $this, 'render_admin_mount' ],
			$icon_svg,
			'99.999'
		);

		add_submenu_page(
			'leat',
			'Onboarding',
			'Onboarding',
			'manage_options',
			'admin.php?page=leat#/onboarding',
			''
		);
	}

	/**
	 * Register frontend assets.
	 */
	public function enqueue_frontend() {
		$has_api_key = $this->connection->has_api_key();

		if ( $has_api_key ) {
			$this->assets_api->register_script(
				self::APP_HANDLE,
				'ts/frontend/index.ts',
				'frontend',
				[ 'wp-api-fetch', 'wp-i18n', 'wp-a11y', 'wp-keycodes', 'wp-html-entities' ]
			);

			wp_register_style( self::APP_HANDLE . '-dynamic', false, [], '1.0.0' );
			wp_enqueue_style( self::APP_HANDLE . '-dynamic' );

			if ( wp_script_is( self::APP_HANDLE, 'enqueued' ) ) {
				$settings = rawurlencode( wp_json_encode( $this->get_plugin_settings() ) );

				$this->assets_api->add_inline_script(
					self::APP_HANDLE,
					"window.leatConfig = JSON.parse(decodeURIComponent('" . esc_js( $settings ) . "'));",
					'before'
				);

				$this->initialize_core_data();

				$wc_settings = rawurlencode( wp_json_encode( $this->wc_settings_data ) );
				$this->assets_api->add_inline_script(
					self::APP_HANDLE,
					"window.leatWcSettings = JSON.parse(decodeURIComponent('" . esc_js( $wc_settings ) . "'));",
					'before'
				);

				$this->assets_api->add_inline_script(
					self::APP_HANDLE,
					$this->get_middleware_config(),
					'before'
				);

				wp_add_inline_style(
					self::APP_HANDLE . '-dynamic',
					$this->get_dynamic_css()
				);
			}
		}
	}

	/**
	 * Register admin assets.
	 */
	public function enqueue_admin() {
		$screen = get_current_screen();

		if ( $this->plugin_screen_hook_suffix !== $screen->id ) {
			return;
		}

		$this->assets_api->register_script(
			self::ADMIN_APP_HANDLE,
			'ts/admin/index.ts',
			'admin',
			[ 'wp-api-fetch', 'wp-i18n', 'wp-a11y', 'wp-keycodes', 'wp-hooks' ]
		);

		if ( wp_script_is( self::ADMIN_APP_HANDLE, 'enqueued' ) ) {
			$this->initialize_core_data();

			$wc_settings = rawurlencode( wp_json_encode( $this->wc_settings_data ) );

			$this->assets_api->add_inline_script(
				self::ADMIN_APP_HANDLE,
				"window.leatWcSettings = JSON.parse(decodeURIComponent('" . esc_js( $wc_settings ) . "'));",
				'before'
			);

			$this->assets_api->add_inline_script(
				self::ADMIN_APP_HANDLE,
				$this->get_middleware_config(),
				'before'
			);
		}
	}

	/**
	 * Render admin mount.
	 */
	public function render_admin_mount() {
		echo wp_kses_post( '<div id="leat-admin-mount"></div>' );
	}

	/**
	 * Get middleware config.
	 *
	 * @return string
	 */
	protected function get_middleware_config() {
		$all_languages    = Common::get_languages();
		$current_language = Common::get_current_language();
		$api_key_set      = $this->connection->has_api_key();
		$user_id          = is_user_logged_in() ? get_current_user_id() : null;
		$contact          = $user_id ? $this->connection->get_contact( $user_id ) : null;
		$uuid             = $contact ? $contact['uuid'] : null;

		return '
            window.leatMiddlewareConfig = {
				apiKeySet: ' . wp_json_encode( $api_key_set ) . ',
				loggedIn: ' . wp_json_encode( is_user_logged_in() ) . ',
				userId: ' . wp_json_encode( $user_id ) . ',
				hasLeatUuid: ' . wp_json_encode( $uuid ) . ",
                siteLanguage: '" . esc_js( get_bloginfo( 'language' ) ) . "',
                currentLanguage: '" . esc_js( $current_language ) . "',
                languages: " . wp_json_encode( $all_languages ) . ",
                storeApiNonce: '" . esc_js( wp_create_nonce( 'wc_store_api' ) ) . "',
                wcStoreApiNonceTimestamp: '" . esc_js( time() ) . "'
            };
        ";
	}

	/**
	 * Get settings via REST API.
	 *
	 * @return array|null
	 */
	protected function get_plugin_settings() {
		$settings = $this->settings->get_all_settings_with_values( false );

		if ( ! $settings ) {
			return null;
		}

		return array_reduce(
			$settings,
			function ( $carry, $item ) {
				$carry[ $item['id'] ] = $item['value'];
				return $carry;
			},
			[]
			);
	}

	/**
	 * Initialize core data.
	 */
	protected function initialize_core_data() {
		$this->wc_settings_data = $this->get_wc_settings();
	}

	/**
	 * Get WooCommerce settings.
	 *
	 * @return array
	 */
	protected function get_wc_settings() {
		return [
			'adminUrl'                      => admin_url(),
			'countries'                     => WC()->countries->get_countries(),
			'countryTaxOrVat'               => WC()->countries->tax_or_vat(),
			'currency'                      => $this->get_currency_data(),
			'currentUserIsAdmin'            => current_user_can( 'manage_woocommerce' ),
			'homeUrl'                       => esc_url( home_url( '/' ) ),
			'locale'                        => $this->get_locale_data(),
			'placeholderImgSrc'             => wc_placeholder_img_src(),
			'taxesEnabled'                  => wc_tax_enabled(),
			'couponsEnabled'                => wc_coupons_enabled(),
			'displayCartPricesIncludingTax' => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			'shippingEnabled'               => wc_shipping_enabled(),
			'baseLocation'                  => wc_get_base_location(),
			'shippingCalculatorEnabled'     => filter_var( get_option( 'woocommerce_enable_shipping_calc' ), FILTER_VALIDATE_BOOLEAN ),
			'showCartPricesIncTax'          => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			'taxTotalDisplayItemized'       => 'itemized' === get_option( 'woocommerce_tax_total_display' ),
			'productsSettings'              => $this->get_products_settings(),
			'siteTitle'                     => get_bloginfo( 'name' ),
			'storePages'                    => $this->get_store_pages(),
			'wcAssetUrl'                    => plugins_url( 'assets/', WC_PLUGIN_FILE ),
			'wcVersion'                     => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'wpLoginUrl'                    => wp_login_url(),
			'wpVersion'                     => get_bloginfo( 'version' ),
			'endpoints'                     => [
				'order-received' => [
					'active' => is_wc_endpoint_url( 'order-received' ),
				],
			],
		];
	}

	/**
	 * Get currency data.
	 *
	 * @return array
	 */
	protected function get_currency_data() {
		$currency = get_woocommerce_currency();

		return [
			'code'              => $currency,
			'precision'         => wc_get_price_decimals(),
			'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
			'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
			'decimalSeparator'  => wc_get_price_decimal_separator(),
			'thousandSeparator' => wc_get_price_thousand_separator(),
			'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
		];
	}

	/**
	 * Get locale data.
	 *
	 * @return array
	 */
	protected function get_locale_data() {
		global $wp_locale;

		return [
			'siteLocale'    => get_locale(),
			'userLocale'    => get_user_locale(),
			'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
		];
	}

	/**
	 * Get store pages.
	 *
	 * @return array
	 */
	protected function get_store_pages() {
		return array_map(
			[ $this, 'format_page_resource' ],
			[
				'myaccount' => wc_get_page_id( 'myaccount' ),
				'shop'      => wc_get_page_id( 'shop' ),
				'cart'      => wc_get_page_id( 'cart' ),
				'checkout'  => wc_get_page_id( 'checkout' ),
				'privacy'   => wc_privacy_policy_page_id(),
				'terms'     => wc_terms_and_conditions_page_id(),
			]
			);
	}

	/**
	 * Get product settings.
	 *
	 * @return array
	 */
	protected function get_products_settings() {
		return [
			'cartRedirectAfterAdd' => get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes',
		];
	}

	/**
	 * Get dynamic CSS output.
	 *
	 * @return string
	 */
	protected function get_dynamic_css() {
		$css = '';
		// Example future CSS content:
		// $css = ".some-class { color: " . $some_variable . "; }";.

		return wp_strip_all_tags( $css );
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

		if ( ! ( $page instanceof WP_Post ) || 'publish' !== $page->post_status ) {
			return [
				'id'        => 0,
				'title'     => '',
				'permalink' => false,
			];
		}

		return [
			'id'        => $page->ID,
			'title'     => $page->post_title,
			'permalink' => get_permalink( $page->ID ),
		];
	}
}
