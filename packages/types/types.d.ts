declare module '@woocommerce/settings' {
	export declare type BillingAddress = Record<string, unknown>;
	export declare type ShippingAddress = Record<string, unknown>;
	export declare type LocaleSpecificFormField = string;
	export declare function getAdminLink(path: string): string;
	export declare function getSetting<T>(
		name: string,
		fallback?: unknown,
		filter = (val: unknown, fb: unknown) => (typeof val !== 'undefined' ? val : fb)
	): T;
	export declare function isWpVersion(
		version: string,
		operator: '>' | '>=' | '=' | '<' | '<='
	): boolean;
}
