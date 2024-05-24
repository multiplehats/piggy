<?php
namespace PiggyWP\StoreApiExtension\Core;

use PiggyWP\StoreApiExtension\AbstractStoreApiExtensionType;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use WC_Product;

/**
 * Free Shipping Meter: StoreApi extension integration
 *
 * @since 3.0.0
 */
final class Common extends AbstractStoreApiExtensionType {
	/**
	 * StoreApi extension name/id/slug (matches id in WC_Gateway_BACS in core).
	 *
	 * @var string
	 */
	protected $name = 'common';

	/**
	 * Schema identifiers
	 *
	 * @var array
	 */
	protected $schema_ids = [ CartSchema::IDENTIFIER ];

	/**
	 * Initializes the StoreApi extension type.
	 */
	public function initialize() {
		$this->settings = array(
			// No offical option for this yet, so it's on by default.
			'enabled' => 'on',
		);
	}

	/**
	 * Returns if this StoreApi extension should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->get_setting( 'enabled', false ) === 'on';
	}

	/**
	 * Returns an array of scripts/handles to be registered for this Store Api extension.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_handles() {
		return [];
	}

	/**
	 * Returns the schema type.
	 */
	public function get_store_api_extension_schema_ids() {
		return $this->schema_ids;
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the Store Api extension script.
	 *
	 * @return array
	 */
	public function get_store_api_extension_script_data() {
		return [];
	}

	/**
	 * Returns an Schema array for the StoreApi.
	 *
	 * @return array
	 */
	public function get_store_api_extension_schema() {
		return [
			'products' => [
				'description' => __( 'Products to recommend.', 'piggy' ),
				'type'        => array( 'array', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			],
		];
	}

	/**
	 * Returns An array of key, value pairs of data made available to
	 * StoreApi extensions client side.
	 *
	 * @param array|null $param The param that's returned from the StoreApi callback (e.g. cart item, product).
	 * @return array
	 */
	public function get_store_api_extension_callback( $param = null ): array {
		return $this->get_cart_total_data();
	}

	/**
	 * Get shipping data
	 *
	 * @return array
	 */
	public function get_cart_total_data(): array {
		$this->cart_controller->load_cart();
		$cart = $this->cart_controller->get_cart_instance();
		$data = [];

		$estimated_for_prefix    = '';
		$customer_outside_base   = false;
		$has_calculated_shipping = false;
		$customer_country        = '';

		if ( ! empty( $cart ) && wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
			$taxable_address = WC()->customer->get_taxable_address();

			if ( ! empty( $taxable_address ) ) {
				$estimated_for_prefix    = WC()->countries->estimated_for_prefix( $taxable_address[0] );
				$customer_outside_base   = WC()->customer->is_customer_outside_base();
				$has_calculated_shipping = WC()->customer->has_calculated_shipping();
				$customer_country        = WC()->countries->countries[ $taxable_address[0] ];
			}
		}

		$data = array(
			'estimatedForPrefix'    => $estimated_for_prefix,
			'customerOutsideBase'   => $customer_outside_base,
			'customerCountry'       => $customer_country,
			'hasCalculatedShipping' => $has_calculated_shipping,
		);

		return $data;
	}
}
