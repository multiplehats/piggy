<?php

namespace PiggyWP;

use PiggyWP\Api\Connection;
use PiggyWP\Settings;
use PiggyWP\Assets\Api as AssetApi;
use PiggyWP\Utils\Common;
use PiggyWP\Utils\Logger;
use WP_REST_Request;
use WP_Post;

/**
 * AssetsController class.
 *
 * @since 5.0.0
 * @internal
 */
final class AssetsController
{
	const APP_HANDLE = 'piggy-frontend';
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
	private $wc_settings_data = [];

	/**
	 * Contains Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Piggy API connection.
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
	 * @param AssetApi $asset_api Asset API interface for various asset registration.
	 * @param Settings $settings Settings interface.
	 * @param Connection $connection Piggy API connection.
	 */
	public function __construct(AssetApi $asset_api, Settings $settings, Connection $connection)
	{
		$this->assets_api = $asset_api;
		$this->settings = $settings;
		$this->connection = $connection;
		$this->init();
		$this->logger = new Logger();
	}

	/**
	 * Initialize class features.
	 */
	protected function init()
	{
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend'], 80);
		add_action('wp_head', [$this, 'enqueue_frontend_dynamic_css'], 90);

		$this->plugin_screen_hook_suffix = add_menu_page(
			'Piggy',
			'Piggy',
			'manage_options',
			'piggy',
			[$this, 'render_admin_mount'],
			'dashicons-cart',
			'99.999'
		);

		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin'], 100);
	}

	/**
	 * Register frontend assets.
	 */
	public function enqueue_frontend()
	{
		$this->assets_api->register_script(
			self::APP_HANDLE,
			'frontend',
			'ts/frontend/index.ts',
			['wp-api-fetch', 'wp-i18n', 'wp-a11y', 'wp-keycodes', 'wp-html-entities']
		);

		if (wp_script_is(self::APP_HANDLE, 'enqueued')) {
			$settings = rawurlencode(wp_json_encode($this->get_plugin_settings()));
			$earn_rules = rawurlencode(wp_json_encode($this->get_earn_rules_config()));
			$spend_rules = rawurlencode(wp_json_encode($this->get_spend_rules_config()));

			$coupons = rawurlencode(wp_json_encode($this->get_coupons_by_user_id(get_current_user_id())));

			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				"window.piggyConfig = JSON.parse(decodeURIComponent('" . esc_js($settings) . "'));",
				'before'
			);

			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				"window.piggyEarnRules = JSON.parse(decodeURIComponent('" . esc_js($earn_rules) . "'));",
				'before'
			);

			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				"window.piggySpentRules = JSON.parse(decodeURIComponent('" . esc_js($spend_rules) . "'));",
				'before'
			);

			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				"window.piggyCoupons = JSON.parse(decodeURIComponent('" . esc_js($coupons) . "'));",
				'before'
			);

			$this->initialize_core_data();

			$wc_settings = rawurlencode(wp_json_encode($this->wc_settings_data));
			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				"window.piggyWcSettings = JSON.parse(decodeURIComponent('" . esc_js($wc_settings) . "'));",
				'before'
			);

			$this->assets_api->add_inline_script(
				self::APP_HANDLE,
				$this->get_middleware_config(),
				'before'
			);

			$piggy_data = $this->get_piggy_data();
			if ($piggy_data) {
				$this->assets_api->add_inline_script(
					self::APP_HANDLE,
					$piggy_data,
					'before'
				);
			}

			wp_add_inline_style(
				self::APP_HANDLE,
				$this->get_dynamic_css()
			);
		}
	}

	/**
	 * Register admin assets.
	 */
	public function enqueue_admin()
	{
		$screen = get_current_screen();

		if ($this->plugin_screen_hook_suffix !== $screen->id) {
			return;
		}

		$this->assets_api->register_script(
			self::ADMIN_APP_HANDLE,
			'admin',
			'ts/admin/index.ts',
			['wp-api-fetch', 'wp-i18n', 'wp-a11y', 'wp-keycodes', 'wp-hooks']
		);

		if (wp_script_is(self::ADMIN_APP_HANDLE, 'enqueued')) {
			$this->initialize_core_data();

			$wc_settings = rawurlencode(wp_json_encode($this->wc_settings_data));

			$this->assets_api->add_inline_script(
				self::ADMIN_APP_HANDLE,
				"window.piggyWcSettings = JSON.parse(decodeURIComponent('" . esc_js($wc_settings) . "'));",
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
	public function render_admin_mount()
	{
		echo '<div id="piggy-admin-mount"></div>';
	}

	/**
	 * Enqueue dynamic CSS.
	 */
	public function enqueue_frontend_dynamic_css()
	{
		echo '<style id="piggy-dynamic-css">' . esc_html($this->get_dynamic_css()) . '</style>';
	}

	/**
	 * Get middleware config.
	 *
	 * @return string
	 */
	protected function get_middleware_config()
	{
		$all_languages = Common::get_languages();
		$current_language = Common::get_current_language();

		return "
            window.piggyMiddlewareConfig = {
				loggedIn: " . json_encode(is_user_logged_in()) . ",
				userId: " . json_encode(get_current_user_id()) . ",
                siteLanguage: '" . esc_js(get_bloginfo('language')) . "',
                currentLanguage: '" . esc_js($current_language) . "',
                languages: " . wp_json_encode($all_languages) . ",
                storeApiNonce: '" . esc_js(wp_create_nonce('wc_store_api')) . "',
                wcStoreApiNonceTimestamp: '" . esc_js(time()) . "'
            };
        ";
	}

	/**
	 * Get piggy data.
	 *
	 * @return string|null
	 */
	protected function get_piggy_data()
	{
		$client = $this->connection->init_client();

		if ($client === null) {
			$this->logger->error("Failed to initialize client");

			return null;
		}

		$user_id = get_current_user_id();
		$uuid = get_user_meta( get_current_user_id(), 'piggy_uuid', true);
		$contact = $uuid ? $this->connection->get_contact( $uuid ) : null;
		$shop_id = get_option('piggy_shop_uuid');
		$shop = $shop_id ? $this->connection->get_shop( $shop_id ) : null;
		$claimed_rewards = $uuid ? $this->connection->get_user_reward_logs( $user_id ) : null;

		return "
            window.piggyData = {
                contact: " . wp_json_encode($contact) . ",
                shop: " . wp_json_encode($shop) . ",
				claimedRewards: " . wp_json_encode($claimed_rewards) . ",
            };
        ";
	}

	/**
	 * Get settings via REST API.
	 *
	 * @return array|null
	 */
	protected function get_plugin_settings()
	{
		$request = new WP_REST_Request('GET', '/piggy/private/settings');
		$response = rest_do_request($request);
		$server = rest_get_server();
		$data = $server->response_to_data($response, false);

		if ( ! $data ) {
			return null;
		}

		return array_map(function ($item) {
			return $item['value'];
		}, $data);
	}

	/**
	 * Get earn rule config
	 *
	 * @return array|null
	 */
	protected function get_earn_rules_config()
	{
		$request = new WP_REST_Request('GET', '/piggy/v1/earn-rules');
		$response = rest_do_request($request);
		$server = rest_get_server();
		$data = $server->response_to_data($response, false);

		if ( ! $data || ! is_array($data) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Get spend rule config
	 *
	 * @return array|null
	 */
	protected function get_spend_rules_config()
	{
		$request = new WP_REST_Request('GET', '/piggy/v1/spend-rules');

		$request->set_param('status', 'publish');

		$response = rest_do_request($request);
		$server = rest_get_server();
		$data = $server->response_to_data($response, false);

		if ( ! $data || ! is_array($data) ) {
			return null;
		}

		// If data is a 40x
		if (isset($data['code'])) {
			return null;
		}

		return $data;
	}

	protected function get_coupons_by_user_id($user_id)
	{
		$request = new WP_REST_Request('GET', '/piggy/v1/coupons');
		$request->set_param('userId', $user_id);

		$response = rest_do_request($request);
		$server = rest_get_server();
		$data = $server->response_to_data($response, false);

		return $data;
	}

	/**
	 * Initialize core data.
	 */
	protected function initialize_core_data()
	{
		$this->wc_settings_data = $this->get_wc_settings();
	}

	/**
	 * Get WooCommerce settings.
	 *
	 * @return array
	 */
	protected function get_wc_settings()
	{
		return [
			'adminUrl' => admin_url(),
			'countries' => WC()->countries->get_countries(),
			'countryTaxOrVat' => WC()->countries->tax_or_vat(),
			'currency' => $this->get_currency_data(),
			'currentUserIsAdmin' => current_user_can('manage_woocommerce'),
			'homeUrl' => esc_url(home_url('/')),
			'locale' => $this->get_locale_data(),
			'placeholderImgSrc' => wc_placeholder_img_src(),
			'taxesEnabled' => wc_tax_enabled(),
			'couponsEnabled' => wc_coupons_enabled(),
			'displayCartPricesIncludingTax' => 'incl' === get_option('woocommerce_tax_display_cart'),
			'shippingEnabled' => wc_shipping_enabled(),
			'baseLocation' => wc_get_base_location(),
			'shippingCalculatorEnabled' => filter_var(get_option('woocommerce_enable_shipping_calc'), FILTER_VALIDATE_BOOLEAN),
			'showCartPricesIncTax' => 'incl' === get_option('woocommerce_tax_display_cart'),
			'taxTotalDisplayItemized' => 'itemized' === get_option('woocommerce_tax_total_display'),
			'productsSettings' => $this->get_products_settings(),
			'siteTitle' => get_bloginfo('name'),
			'storePages' => $this->get_store_pages(),
			'wcAssetUrl' => plugins_url('assets/', WC_PLUGIN_FILE),
			'wcVersion' => defined('WC_VERSION') ? WC_VERSION : '',
			'wpLoginUrl' => wp_login_url(),
			'wpVersion' => get_bloginfo('version'),
			'endpoints' => [
				'order-received' => [
					'active' => is_wc_endpoint_url('order-received'),
				],
			],
		];
	}

	/**
	 * Get currency data.
	 *
	 * @return array
	 */
	protected function get_currency_data()
	{
		$currency = get_woocommerce_currency();

		return [
			'code' => $currency,
			'precision' => wc_get_price_decimals(),
			'symbol' => html_entity_decode(get_woocommerce_currency_symbol($currency)),
			'symbolPosition' => get_option('woocommerce_currency_pos'),
			'decimalSeparator' => wc_get_price_decimal_separator(),
			'thousandSeparator' => wc_get_price_thousand_separator(),
			'priceFormat' => html_entity_decode(get_woocommerce_price_format()),
		];
	}

	/**
	 * Get locale data.
	 *
	 * @return array
	 */
	protected function get_locale_data()
	{
		global $wp_locale;

		return [
			'siteLocale' => get_locale(),
			'userLocale' => get_user_locale(),
			'weekdaysShort' => array_values($wp_locale->weekday_abbrev),
		];
	}

	/**
	 * Get store pages.
	 *
	 * @return array
	 */
	protected function get_store_pages()
	{
		return array_map([$this, 'format_page_resource'], [
			'myaccount' => wc_get_page_id('myaccount'),
			'shop' => wc_get_page_id('shop'),
			'cart' => wc_get_page_id('cart'),
			'checkout' => wc_get_page_id('checkout'),
			'privacy' => wc_privacy_policy_page_id(),
			'terms' => wc_terms_and_conditions_page_id(),
		]);
	}

	/**
	 * Get product settings.
	 *
	 * @return array
	 */
	protected function get_products_settings()
	{
		return [
			'cartRedirectAfterAdd' => get_option('woocommerce_cart_redirect_after_add') === 'yes',
		];
	}

	/**
	 * Get dynamic CSS output.
	 *
	 * @return string
	 */
	protected function get_dynamic_css()
	{
		return '';
	}

	/**
	 * Format a page object into a standard array of data.
	 *
	 * @param WP_Post|int $page Page object or ID.
	 * @return array
	 */
	protected function format_page_resource($page)
	{
		if (is_numeric($page) && $page > 0) {
			$page = get_post($page);
		}

		if (!($page instanceof WP_Post) || 'publish' !== $page->post_status) {
			return [
				'id' => 0,
				'title' => '',
				'permalink' => false,
			];
		}

		return [
			'id' => $page->ID,
			'title' => $page->post_title,
			'permalink' => get_permalink($page->ID),
		];
	}
}
