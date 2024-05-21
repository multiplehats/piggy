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
final class WPCleverWoosb implements CompatInterface
{
	/**
	 * StoreApi extension name/id/slug
	 *
	 * @var string
	 */
	protected $integration_id = 'wpclever_woosb';

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
		if (!class_exists('WPCleverWoosb')) {
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
			'is_child' => array(
				'description' => __( 'Whether the item is a child product.', 'piggy' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'quantity' => array(
				'description' => __( 'Quantity of the item being added to the cart.', 'piggy' ),
				'type'        => array( 'integer', 'null' ),
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
			'fixed_price' => array(
				'description' => __( 'Fixed price of the item being added to the cart.', 'piggy' ),
				'type'        => array( 'boolean' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'discount_amount' => array(
				'description' => __( 'Discount amount of the item being added to the cart.', 'piggy' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'discount_percentage' => array(
				'description' => __( 'Discount percentage of the item being added to the cart.', 'piggy' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	public function is_parent( $param = array() ): bool {
		if( isset( $param['woosb_key'] ) ) {
			if( !isset( $param['woosb_parent_id'] ) ) {
				return true;
			}
		}

		return false;
	}

	public function is_child( $param = array() ): bool {
		if( isset( $param['woosb_key'] ) ) {
			$parent_key = isset( $param['woosb_parent_key'] ) ? $param['woosb_parent_key'] : null;
			$key = $param['woosb_key'];

			if( $parent_key !== $key ) {
				return true;
			}
		}

		return false;
	}

	public function is_integration_cart_item( $param = array() ): bool {
		if( isset( $param['woosb_key'] ) ) {
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
		return array(
			'disable_quantity_change' => get_post_meta( $param['product_id'], 'woosb_optional_products', true ) === 'off' ? false : true,
			'disable_price' => false,
			'is_parent' => $this->is_parent( $param ),
			'is_child' => $this->is_child( $param ),
			'quantity' => isset( $param['woosb_quantity'] ) ? $param['woosb_quantity'] : null,
			'parent_id' => isset( $param['woosb_parent_id'] ) ? $param['woosb_parent_id'] : null,
			'parent_key' => isset( $param['woosb_parent_key'] ) ? $param['woosb_parent_key'] : null,
			'fixed_price' => isset( $param['woosb_fixed_price'] ) ? $param['woosb_fixed_price'] : null,
			'discount_amount' => isset( $param['woosb_discount_amount'] ) ? $param['woosb_discount_amount'] : null,
			'discount_percentage' => isset( $param['woosb_discount'] ) ? $param['woosb_discount'] : null,
		);
	}
}
