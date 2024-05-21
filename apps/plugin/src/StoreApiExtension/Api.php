<?php

namespace Piggy\StoreApiExtension;

use Piggy\Options;
use Piggy\Package;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Piggy\StoreApiExtension\Core\Common as Common;
use Piggy\StoreApiExtension\Core\FreeShippingMeter;
use Piggy\StoreApiExtension\Core\ProductSuggestions;
use Piggy\StoreApiExtension\Compat\CompatRegistry;

/**
 * The Api class provides an interface to StoreApi extension registration.
 *
 * @since 1.0.0
 */
class Api {
	/**
	 * Reference to the StoreApiExtensionRegistry instance.
	 *
	 * @var StoreApiExtensionRegistry
	 */
	private $store_api_extension_registry;

	/**
	 * Options
	 *
	 * @var Options
	 */
	private $options;

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
	 * @param Options                   $options Options.
	 */
	public function __construct( StoreApiExtensionRegistry $store_api_registry, ExtendSchema $extend, Options $options ) {
		$this->store_api_extension_registry = $store_api_registry;
		$this->options                      = $options;
		$this->extend                       = $extend;
		$this->init();
	}

	/**
	 * Initialize class features.
	 * Note: Order and priority of these actions is important.
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register_api_extensions' ), 10 );
		add_action( 'rest_api_init', array( $this, 'add_store_api_data' ), 15 );
	}

	/**
	 * Register StoreApi extensions.
	 */
	public function register_api_extensions() {
		$this->store_api_extension_registry->register(
			Package::container()->get( FreeShippingMeter::class )
		);
		$this->store_api_extension_registry->register(
			Package::container()->get( ProductSuggestions::class )
		);
		$this->store_api_extension_registry->register(
			Package::container()->get( CompatRegistry::class )
		);
		$this->store_api_extension_registry->register(
			Package::container()->get( Common::class )
		);

		$this->store_api_extension_registry->initialize();
	}

	/**
	 * Extend StoreApi endpoints.
	 */
	public function add_store_api_data() {
		$store_api_extended_endppoints = $this->store_api_extension_registry->get_all_registered_endpoint_extensions();


		if ( ! empty( $store_api_extended_endppoints ) && is_array( $store_api_extended_endppoints ) ) {
			foreach ( $store_api_extended_endppoints as $endpoint => $api ) {
				$this->extend->register_endpoint_data(
					array(
						'endpoint'        => $endpoint,
						'namespace'       => 'piggy',
						'data_callback'   => function ($param = null) use ( $api ) {
							// Default $param to null because only `wc/store/cart/items`, `wc/store/products`
							// endpoints pass the $param argument.
							$data = [];

							foreach ( $api as $name => $value ) {

								$data[ $name ] = call_user_func( $value['callback'], $param );
							}

							return $data;
						},
						'schema_callback' => function () use ( $api ) {
							$schema = [];

							foreach ( $api as $name => $value ) {
								$schema[ $name ] = $value['schema'];
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
