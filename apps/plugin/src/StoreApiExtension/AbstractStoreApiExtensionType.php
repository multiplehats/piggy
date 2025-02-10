<?php

namespace Leat\StoreApiExtension;

use Leat\Options;
use Leat\StoreApiExtension\StoreApiExtensionTypeInterface;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Exception;
use Leat\Api\Connection;
use Leat\Settings;

/**
 * AbstractPaymentMethodType class.
 *
 * @since 2.6.0
 */
abstract class AbstractStoreApiExtensionType implements StoreApiExtensionTypeInterface
{
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Schema identifiers
	 *
	 * @var array
	 */
	protected $schema_ids = array();

	/**
	 * Rest extend instance.
	 *
	 * @var ExtendSchema
	 */
	protected $extend;

	/**
	 * Cart controller class instance.
	 *
	 * @var CartController
	 */
	protected $cart_controller;

	/**
	 * An instance of the Settings class.
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * An instance of the Connection class.
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Stores schema_controller.
	 *
	 * @var SchemaController
	 */
	protected $schema_controller;

	/**
	 * Stores schema_controller.
	 *
	 * @var ProductSchema
	 */
	protected $product_schema;

	/**
	 * Stores schema_controller.
	 *
	 * @var CartSchema
	 */
	protected $cart_schema;

	/**
	 * Stores schema_controller.
	 *
	 * @var CartSchema
	 */
	protected $cart_item_schema;

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema     $extend Rest Extending instance.
	 * @param SchemaController $schema_controller Schema controller instance.
	 * @param Connection       $connection Connection instance.
	 * @param Settings          $settings An instance of Settings.
	 */
	public function __construct(ExtendSchema $extend, SchemaController $schema_controller, Connection $connection, Settings $settings)
	{
		$this->extend            = $extend;
		$this->schema_controller = $schema_controller;
		$this->connection        = $connection;
		$this->settings          = $settings;
		$this->cart_controller   = new CartController();
		$this->product_schema    = new ProductSchema($extend, $schema_controller);
		$this->cart_schema       = new CartSchema($extend, $schema_controller);
		$this->cart_item_schema  = new CartItemSchema($extend, $schema_controller);
	}

	/**
	 * Schema identifiers.
	 *
	 *  @return string[]
	 */
	public function get_schema_ids()
	{
		return $this->schema_ids;
	}

	/**
	 * Returns the name of the payment method.
	 */
	public function get_name()
	{
		return $this->name;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		return true;
	}

	/**
	 * Returns an array of script handles to enqueue for this payment method in
	 * the frontend context
	 *
	 * @return string[]
	 */
	public function get_store_api_extension_script_handles()
	{
		return array();
	}

	/**
	 * Returns an array of script handles to enqueue for this payment method in
	 * the admin context
	 *
	 * @return string[]
	 */
	public function get_store_api_extension_script_handles_for_admin()
	{
		return $this->get_store_api_extension_script_handles();
	}

	/**
	 * An array of key, value pairs of data made available to payment methods
	 * client side.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_data()
	{
		return array();
	}

	/***
	 * An array of key, value pairs of data made available to payment methods
	 */
	public function get_callback()
	{
		return array($this, 'get_store_api_extension_callback');
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * Alias of get_store_api_extension_script_handles. Defined by IntegrationInterface.
	 *
	 * @return string[]
	 */
	public function get_script_handles()
	{
		return $this->get_store_api_extension_script_handles();
	}


	/**
	 * An array of key, value pairs of data made available to payment methods
	 * client side.
	 *
	 * @return array
	 */
	public function get_schemas()
	{
		return $this->get_store_api_extension_schema();
	}

	/**
	 * An array of key, value pairs of data made available to payment methods
	 * client side.
	 *
	 * @return array
	 */
	public function get_api_extension_script_data()
	{
		return array();
	}

	/**
	 * Returns an array of script handles to enqueue in the admin context.
	 *
	 * Alias of get_payment_method_script_handles_for_admin. Defined by IntegrationInterface.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles()
	{
		return $this->get_store_api_extension_script_handles_for_admin();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * Alias of get_payment_method_data. Defined by IntegrationInterface.
	 *
	 * @return array
	 */
	public function get_script_data()
	{
		return $this->get_api_extension_script_data();
	}

	/**
	 * Makes the cart and sessions available to a route by loading them from core.
	 */
	private function load_cart()
	{
		if (! did_action('woocommerce_load_cart_from_session') && function_exists('wc_load_cart')) {
			wc_load_cart();
		}
	}

	/**
	 * Get main instance of cart class.
	 *
	 * @throws Exception When cart cannot be loaded.
	 * @return \WC_Cart
	 */
	protected function get_cart_instance()
	{
		if (! function_exists('WC')) {
			throw new \Exception(__('WooCommerce function does not exist.', 'leat-crm'));
		}

		$this->load_cart();

		$cart = WC()->cart;

		return $cart;
	}

	/**
	 * Adds currency data to an array of monetary values.
	 *
	 * @param array $values Monetary amounts.
	 * @return array Monetary amounts with currency data appended.
	 */
	protected function prepare_currency_response($values)
	{
		return $this->extend->get_formatter('currency')->format($values);
	}

	/**
	 * Convert monetary values from WooCommerce to string based integers, using
	 * the smallest unit of a currency.
	 *
	 * @param string|float $amount Monetary amount with decimals.
	 * @param int          $decimals Number of decimals the amount is formatted with.
	 * @param int          $rounding_mode Defaults to the PHP_ROUND_HALF_UP constant.
	 * @return string      The new amount.
	 */
	protected function prepare_money_response($amount, $decimals = 2, $rounding_mode = PHP_ROUND_HALF_UP)
	{
		return $this->extend->get_formatter('money')->format(
			$amount,
			array(
				'decimals'      => $decimals,
				'rounding_mode' => $rounding_mode,
			)
		);
	}

	/**
	 * Prepares HTML based content, such as post titles and content, for the API response.
	 *
	 * @param string|array $response Data to format.
	 * @return string|array Formatted data.
	 */
	protected function prepare_html_response($response)
	{
		return $this->extend->get_formatter('html')->format($response);
	}
}
