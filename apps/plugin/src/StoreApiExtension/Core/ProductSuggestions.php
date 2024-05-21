<?php
namespace Piggy\StoreApiExtension\Core;

use Piggy\StoreApiExtension\AbstractStoreApiExtensionType;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Piggy\Utils\Common as Utils;
use WC_Product;

/**
 * Free Shipping Meter: StoreApi extension integration
 *
 * @since 3.0.0
 */
final class ProductSuggestions extends AbstractStoreApiExtensionType {
	/**
	 * StoreApi extension name/id/slug
	 *
	 * @var string
	 */
	protected $name = 'product_suggestions';

	/**
	 * Schema identifiers
	 *
	 * @var array
	 */
	protected $schema_ids = array( CartSchema::IDENTIFIER );

	/**
	 * Recommendation product ids.
	 *
	 * @var array
	 */
	protected $suggestion_ids = array();

	/**
	 * Initializes the StoreApi extension type.
	 */
	public function initialize() {
		$this->settings = array(
			'enabled'     => $this->options->get( 'product_suggestions_enable' ),
			'type'        => $this->options->get( 'product_suggestions_type' ),
			'headline'    => $this->options->get( 'product_suggestions_headline' ),
			'button_type' => $this->options->get( 'product_suggestions_button_type' ),
			'button_text' => $this->options->get( 'product_suggestions_button_text' ),
			'products'    => $this->options->get( 'product_suggestions_custom_products' ),
		);

		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 100, 4 );
	}

	/**
	 * Add cart item data.
	 *
	 * @param array $cart_item_data Array of other cart item data.
	 * @param int   $product_id ID of the product added to the cart.
	 * @param int   $variation_id ID of the variation added to the cart.
	 * @param int   $quantity Quantity of the product added to the cart.
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
		if ( in_array( $product_id, $this->suggestion_ids, true ) ) {
			$cart_item_data['piggy']['suggestions'] = true;
		}

		return $cart_item_data;
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
		return array();
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
		return array();
	}

	/**
	 * Returns an Schema array for the StoreApi.
	 *
	 * @return array
	 */
	public function get_store_api_extension_schema() {
		return array(
			'products' => array(
				'description' => __( 'Products to suggest in the cart.', 'piggy' ),
				'type'        => array( 'array', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Returns An array of key, value pairs of data made available to
	 * StoreApi extensions client side.
	 *
	 * @param array|null $param The param that's returned from the StoreApi callback (e.g. cart item, product).
	 * @return array
	 */
	public function get_store_api_extension_callback( $param = null ): array {
		return $this->get_product_recommendation_data();
	}

	/**
	 * Get shipping data
	 *
	 * @return array
	 */
	public function get_product_recommendation_data(): array {
		$this->cart_controller->load_cart();
		$cart            = $this->cart_controller->get_cart_instance();
		$product_ids     = array();
		$in_cart         = array();
		$global_products = array();

		if ( ! empty( $cart ) && 'global_products' === $this->settings['type'] ) {
			$cart_contents = $cart->get_cart();
			$product_ids   = $this->settings['products'];

			if ( ! empty( $cart_contents ) ) {
				foreach ( $cart_contents as $cart_item_key => $values ) {
					if ( $values['quantity'] > 0 ) {
						$in_cart[] = $values['product_id'];
					}
				}
			}

			$product_ids = array_diff( $product_ids, $in_cart );

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );

				if ( Utils::is_woocommerce_product( $product ) ) {
					$product_response = $this->get_item_response( $product );

					if ( ! empty( $product_response ) ) {
						$global_products[] = $product_response;
					}
				}
			}
		}

		$data = array(
			'products' => array(
				'global' => $global_products,
			),
		);

		return $data;
	}


	/**
	 * Get a product object to be added to the cart.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	protected function get_item_response( \WC_Product $product ) {
		$this->suggestion_ids[] = $product->get_id();

		return $this->product_schema->get_item_response( $product );
	}
}
