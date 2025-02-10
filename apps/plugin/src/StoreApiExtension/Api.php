<?php

namespace Leat\StoreApiExtension;

use Leat\Package;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Leat\Settings;
use Leat\StoreApiExtension\Core\Common as Common;
use Leat\StoreApiExtension\Compat\CompatRegistry;

/**
 * The Api class provides an interface to StoreApi extension registration.
 *
 * @since 2.0.0
 */
class Api
{
	/**
	 * Reference to the StoreApiExtensionRegistry instance.
	 *
	 * @var StoreApiExtensionRegistry
	 */
	private $store_api_extension_registry;

	/**
	 * Options
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private $extend;

	/**
	 * Constructor.
	 *
	 * @param StoreApiExtensionRegistry $store_api_registry An instance of Store Api Registry.
	 * @param ExtendSchema              $extend An instance of ExtendSchema.
	 * @param Settings                  $settings An instance of Settings.
	 */
	public function __construct(StoreApiExtensionRegistry $store_api_registry, ExtendSchema $extend, Settings $settings)
	{
		$this->store_api_extension_registry = $store_api_registry;
		$this->settings                     = $settings;
		$this->extend                       = $extend;
		$this->init();
	}

	/**
	 * Initialize class features.
	 * Note: Order and priority of these actions is important.
	 */
	protected function init()
	{
		add_action('init', array($this, 'register_api_extensions'), 10);
		add_action('rest_api_init', array($this, 'add_store_api_data'), 15);
	}

	/**
	 * Register StoreApi extensions.
	 */
	public function register_api_extensions()
	{
		$this->store_api_extension_registry->register(
			Package::container()->get(CompatRegistry::class)
		);
		$this->store_api_extension_registry->register(
			Package::container()->get(Common::class)
		);

		$this->store_api_extension_registry->initialize();
	}

	/**
	 * Extend StoreApi endpoints.
	 */
	public function add_store_api_data()
	{
		$store_api_extended_endppoints = $this->store_api_extension_registry->get_all_registered_endpoint_extensions();


		if (! empty($store_api_extended_endppoints) && is_array($store_api_extended_endppoints)) {
			foreach ($store_api_extended_endppoints as $endpoint => $api) {
				$this->extend->register_endpoint_data(
					array(
						'endpoint'        => $endpoint,
						'namespace'       => 'leat-crm',
						'data_callback'   => function ($param = null) use ($api) {
							// Default $param to null because only `wc/store/cart/items`, `wc/store/products`
							// endpoints pass the $param argument.
							$data = [];

							foreach ($api as $name => $value) {

								$data[$name] = call_user_func($value['callback'], $param);
							}

							return $data;
						},
						'schema_callback' => function () use ($api) {
							$schema = [];

							foreach ($api as $name => $value) {
								$schema[$name] = $value['schema'];
							}

							return $schema;
						},
						'schema_type'     => ARRAY_A,
					)
				);
			}
		}
	}
}
