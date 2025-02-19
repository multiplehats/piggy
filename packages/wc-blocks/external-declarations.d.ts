/**
 * External dependencies declarations
 */

declare module "@woocommerce/settings" {
	const getSetting: (key: string) => string;
}

declare module "@woocommerce/blocks-checkout" {
	const __experimentalRegisterCheckoutFilters: (
		extensionName: string,
		filters: Record<string, (...args: any[]) => any>
	);
}

declare module "@woocommerce/blocks-registry" {
	const registerPaymentMethodExtensionCallbacks: (
		extensionName: string,
		callbacks: Record<string, (...args: any[]) => any>
	);
}
