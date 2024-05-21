<?php
namespace PiggyWP\Utils;

/**
 * Common Helper class.
 */
class Common {
	/**
	 * Check if the resource is array.
	 *
	 * @param  mixed $resource The resource to check.
	 * @return bool
	 */
	public static function is_not_empty_array( $resource ) {
		return ( is_array( $resource ) && ! empty( $resource ) );
	}

	/**
	 * Check if the resource is array.
	 *
	 * @param  mixed $product The product to check.
	 * @return bool
	 */
	public static function is_woocommerce_product ( $product ) {
		return ( $product instanceof \WC_Product );
	}
}
