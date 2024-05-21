export const INTEGRATIONS = {
	/**
	 * WPClever WooCommerce Bundles
	 *
	 * @see  https://wordpress.org/plugins/woo-product-bundle/
	 * @src apps/plugin/src/StoreApiExtension/Compat/WPCleverWoosb.php
	 */
	WPCLEVER_WOOSB: 'wpclever_woosb',
	CHAINED_PRODUCTS: 'chained_products'
} as const;

export type Integration = keyof typeof INTEGRATIONS;
export type IntegrationValue = typeof INTEGRATIONS[keyof typeof INTEGRATIONS];
