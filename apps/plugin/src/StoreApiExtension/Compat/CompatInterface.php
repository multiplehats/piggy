<?php
namespace PiggyWP\StoreApiExtension\Compat;

use Automattic\WooCommerce\StoreApi\Utilities\CartController;

interface CompatInterface {
	public function __construct( CartController $cart_controller );
	public function integration_id() : string;
	public function is_active() : bool;
	public function get_schema_ids() : array;
	public function get_schema() : array;
	public function is_integration_cart_item( array $param ) : bool;
	/**
	 * Returns An array of key, value pairs of data made available to
	 * StoreApi extensions client side.
	 *
	 * @param array|null $param The param that's returned from the StoreApi callback (e.g. cart item, product).
	 * @return array
	 */
	public function get_callback( $param = null ): array;
}
