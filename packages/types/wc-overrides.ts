/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import type { PiggyCartExtensionData } from './extension-data';
import { INTEGRATIONS } from './integrations';
import type { Cart, CartItem, CartResponse } from './wc-types';

export type PiggyCartitemExtensionData = {
	piggy: {
		compatibility?: {
			/**
			 * Integration with WPClever - WooCommerce Product Bundles.
			 * plugin/src/StoreApiExtension/Compat/WPCleverWoosb.php
			 */
			[INTEGRATIONS.WPCLEVER_WOOSB]?: {
				disable_quantity_change: boolean;
				disable_price?: boolean;
				is_parent: boolean | null;
				is_child: boolean | null;
				parent_id: number | null;
				parent_key: string | null;
				quantity: number | null;
				fixed_price: boolean;
				discount_amount: string | null;
				discount_percentage: string | null;
			} | null;
			[INTEGRATIONS.CHAINED_PRODUCTS]?: {
				disable_quantity_change: boolean;
				disable_price?: boolean;
				is_parent: boolean | null;
				is_child: boolean | null;
				parent_id: number | null;
				parent_key: string | null;
			} | null;
		};
	};
};

export type _CartItem = CartItem & {
	extensions: Record<string, unknown> | (Record<string, never> & PiggyCartitemExtensionData);
};

export type _Cart = Cart & {
	extensions: Record<string, unknown> | (Record<string, never> & PiggyCartExtensionData);
};

export type _CartResponse = CartResponse & {
	extensions: Record<string, unknown> | (Record<string, never> & PiggyCartExtensionData);
};

/**
 * Context to be used when adding a product to the cart.
 * This is used to track where the add to cart event is coming from.
 */
export interface PiggyAddToCartContext {
	add_to_cart_source: 'product_suggestions';
	cart_type: 'drawer' | 'popup' | 'bar';
}
