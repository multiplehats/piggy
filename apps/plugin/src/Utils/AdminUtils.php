<?php
namespace PiggyWP\Utils;

use PiggyWP\Options;
use PiggyWP\Utils\Common;
use WooCommerce;

/**
 * Common Helper class.
 */
class AdminUtils {
	/**
	 * Contains options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options   Options interface.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Check if it's a valid product.
	 *
	 * @param \WC_Product $product Product object.
	 */
	public function is_product( \WC_Product $product ) {
		return $product && is_a( $product, 'WC_Product' );
	}

	/**
	 * Filter an array of product IDs to only include those that are "readable".
	 *
	 * @param array $product_ids The products ids to check.
	 *
	 * @return array
	 */
	public function filter_readable_products( $product_ids ) {
		if ( ! Common::is_not_empty_array( $product_ids ) ) {
			return array();
		}

		if ( function_exists( 'wc_products_array_filter_readable' ) ) {
			return array_filter( array_map( 'wc_get_product', $product_ids ), 'wc_products_array_filter_readable' );
		} else {
			return array_filter( array_map( 'wc_get_product', $product_ids ), array( $this, 'filter_readable_products_callback' ) );
		}
	}

	/**
	 * Callback function for filtering readable products.
	 *
	 * @param \WC_Product $product Product object.
	 */
	public function filter_readable_products_callback( $product ) {
		$this->is_product( $product ) && current_user_can( 'read_product', $product->get_id() );
	}

	/**
	 * Format an array of product IDs.
	 *
	 * @param \WC_Product $product Product object.
	 */
	public function get_admin_product_response( \WC_Product $product ) {
		if ( ! $this->is_product( $product ) ) {
			return array();
		}

		return array(
			'id'         => $product->get_id(),
			'name'       => $product->get_formatted_name(),
			'link'       => $product->get_permalink(),
			'image'      => wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' )[0],
			'categories' => array_map(
				function( $category_id ) {
					if ( ! $category_id ) {
						return;
					}

					$category = get_term( $category_id );

					return array(
						'id'   => $category->term_id,
						'name' => $category->name,
					);
				},
				$product->get_category_ids()
			),
		);
	}
}
