<?php
namespace Piggy\Domain\Services;

use Piggy\Domain\Package;
use WP_REST_Request;
use WP_REST_Server;
use WC_Order;
use WC_Order_Item;

/**
 * Service class for tracking orders made thanks to Piggy.
 *
 * @internal
 */
class OrderContext {
	const ADD_ITEM_ROUTE   = '/wc/store/v1/cart/add-item';
	const META_DATA_PREFIX = 'piggy_';

	/**
	 * Holds the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Constructor
	 *
	 * @param Package $package An instance of the package class.
	 */
	public function __construct( Package $package ) {
		$this->package = $package;
	}

	/**
	 * Set all hooks related to adding Checkout Draft order functionality to Woo Core.
	 */
	public function init() {
		add_filter( 'rest_pre_dispatch', array( $this, 'rest_pre_dispatch' ), 10, 3 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'checkout_create_order' ) );
	}

	/**
	 * Filter the REST response before it is dispatched.
	 *
	 * @param mixed           $result  Response to replace the requested version with. Can be anything a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 */
	public function rest_pre_dispatch( $result, $server, $request ) {
		if ( str_contains( $request->get_route(), self::ADD_ITEM_ROUTE ) ) {
			$params      = $request->get_params();
			$product_id  = $params['id'];

			if ( isset( $params['piggy'] ) ) {
				$this->handle_add_cart_item( $product_id, $params['piggy'] );
			}
		}
		return $result;
	}

	/**
	 * Handle the add to cart item.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $plugin_data The plugin data.
	 */
	public function handle_add_cart_item( $product_id, $plugin_data ) {
		add_filter(
			'woocommerce_add_cart_item_data',
			function( $cart_item_data, $_product_id, $variation_id ) use ( $product_id, $plugin_data ) {
				$cart_item_data['piggy'] = array();

				// Check if the product ID matches the given product ID.
				if ( $_product_id === $product_id ) {
					$cart_item_data['piggy'] = $plugin_data;
				}

				// Return the cart item data.
				return $cart_item_data;
			},
			10,
			3
		);
	}

	/**
	 * Create the order.
	 *
	 * @param WC_Order $order The order.
	 */
	public function checkout_create_order( $order ) {
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			// TODO: @chris - Refactor this legacy code.
			$meta = isset( $item->legacy_values['piggy'] ) ? $item->legacy_values['piggy'] : null;

			// @Chris: We safeguard so that we don't blatenly add all sorts of meta data by accident.
			$allowed_meta_keys = array(
				'context',
				'analytics',
			);

			// Loop through the meta data and add it to the order.
			foreach ( $meta as $key => $value ) {
				if ( in_array( $key, $allowed_meta_keys, true ) ) {
					$this->update_order_meta( $item, $key, $value );
				}
			}
		}
	}

	/**
	 * Update the order meta data, with a prefix and sanitization.
	 *
	 * @param WC_Order_Item $order_item The order item.
	 */
	public function update_order_meta( $order_item, $key, $value ) {
		// Pretty slim chance that this will happen, but we need to safeguard.
		if ( ! is_serialized( $value ) ) {
			$value = maybe_serialize( $value );
		}

		return $order_item->update_meta_data( self::META_DATA_PREFIX . sanitize_key( $key ), sanitize_text_field( $value ) );
	}
}
