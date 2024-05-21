<?php

namespace Piggy\StoreApiExtension\Compat;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;

/**
 * WPC Product Bundles for WooCommerce compatibility.
 *
 * @see https://wordpress.org/plugins/woo-product-bundle/
 * @since 1.0.0
 */
final class ChainedProducts implements CompatInterface
{
	/**
	 * StoreApi extension name/id/slug
	 *
	 * @var string
	 */
	protected $integration_id = 'chained_products';

	/**
	 * Schema identifiers
	 *
	 * @var array
	 */
	protected $schema_ids = array( CartItemSchema::IDENTIFIER );

	/**
	 * Cart controller class instance.
	 *
	 * @var CartController
	 */
	private $cart_controller;

	public function __construct( CartController $cart_controller ) {
		$this->cart_controller = $cart_controller;
	}

	public function integration_id(): string
	{
		return $this->integration_id;
	}

	/**
	 * Returns if this StoreApi extension should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active(): bool {
		if (!class_exists('WC_Admin_Chained_Products')) {
			return false;
		}

		return true;
	}

	public function get_schema_ids(): array {
		return $this->schema_ids;
	}

	public function get_schema(): array {
		return array(
			'is_parent' => array(
				'description' => __( 'Whether the item is a parent product.', 'piggy' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'disable_quantity_change' => array(
				'description' => __( 'Whether the item allows quantity change.', 'piggy' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'disable_price' => array(
				'description' => __( 'Whether the item should show a price', 'piggy' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_child' => array(
				'description' => __( 'Whether the item is a child product.', 'piggy' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'parent_id' => array(
				'description' => __( 'Parent product ID.', 'piggy' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'parent_key' => array(
				'description' => __( 'Parent product key.', 'piggy' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	public function is_parent( array $param ): bool {
		global $wc_chained_products;

		$product_id = $param['product_id'] ?? null;

		return $wc_chained_products->has_chained_products( $product_id );
	}

	public function is_child( array $param ): bool {
		if ( ! isset( $param['chained_item_of'] ) ) {
			return false;
		}

		return true;
	}

	public function get_child( array $param ): array {
		if ( !$this->is_child( $param ) ) {
			return array();
		}

		// Contains the cart item key of the parent product.
		$parent_key = $param['chained_item_of'];
		$parent_item = $this->cart_controller->get_cart_item( $parent_key );

		return array(
			'is_child' => true,
			'parent_id' => $parent_item['product_id'],
			'parent_key' => $parent_key,
		);
	}

	public function is_integration_cart_item( array $param ): bool {
		$is_child = $this->is_child( $param );
		$is_parent = $this->is_parent( $param );

		if ( $is_child || $is_parent ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns An array of key, value pairs of data made available to
	 * StoreApi extensions client side.
	 *
	 * @param array $param The param that's returned from the StoreApi callback (e.g. cart item, product).
	 * @return array
	 */
	public function get_callback( $param = array() ): array {
		$child = $this->get_child( $param );

		return array(
			'is_parent' => $this->is_parent( $param ),
			'is_child' => $child['is_child'] ?? false,
			'disable_quantity_change' => $child['is_child'] ?? false,
			'disable_price' => $child['is_child'] ?? false,
			'parent_id' => $child['parent_id'] ?? null,
			'parent_key' => $child['parent_key'] ?? null,
		);
	}
}
