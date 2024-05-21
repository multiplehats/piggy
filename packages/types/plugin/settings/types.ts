import { z } from 'zod';
import type { Countries } from '../../countries';
import type { SymbolPosition } from '../../wc-types';
import * as adminFields from './adminTypes';

/**
 * Transforms a Zod schema by extracting the `value` field from each property.
 * Assumes that each property of the schema is an object that includes a `value` key.
 */
function transformSchema<T extends z.ZodRawShape>(
	schema: z.ZodObject<T>
): z.ZodObject<{ [K in keyof T]: ExtractValue<T[K]> }> {
	const newShape: Partial<{ [K in keyof T]: ExtractValue<T[K]> }> = {};

	// Iterate over each key in the original schema shape
	for (const key in schema.shape) {
		if (schema.shape.hasOwnProperty(key)) {
			const originalField = schema.shape[key as keyof T];
			// Safely checking if 'value' exists in 'originalField.shape'
			if (
				originalField &&
				typeof originalField === 'object' &&
				'shape' in originalField &&
				typeof originalField.shape === 'object' &&
				originalField?.shape &&
				'value' in originalField.shape
			) {
				newShape[key as keyof T] = originalField.shape.value as ExtractValue<T[keyof T]>;
			} else {
				// Optionally handle cases where 'value' does not exist or the shape is different
				throw new Error(`The field at key '${key}' does not match the expected structure.`);
			}
		}
	}

	// The new shape is explicitly cast to the full type as all fields are now assigned
	return z.object(newShape as { [K in keyof T]: ExtractValue<T[K]> });
}

// Utility type to extract the 'value' type from a Zod schema if it follows a specific pattern
type ExtractValue<S> = S extends z.ZodObject<infer U>
	? U extends { value: infer V }
		? V
		: never
	: never;

/**
 * Options interface for both the frontend and admin.
 * This is the base schema that is extended for each context.
 *
 * @note Do not specify fields here that are only used in the admin!
 */
export const zBasePluginOptions = z.object({
	plugin_enable: adminFields.zToggle,
	plugin_reset: adminFields.zToggle
});

/**
 * Admin options interface.
 * This schema extends the base schema and adds admin-specific fields.
 */
export const zPluginOptionsAdmin = zBasePluginOptions.extend({
	api_key: adminFields.zApiKey
});
export type PluginOptionsAdmin = z.infer<typeof zPluginOptionsAdmin>;
export type PluginOptionsAdminKeys = keyof PluginOptionsAdmin;

/**
 * Frontend options interface.
 * This schema extracts the `value` field from each property of the base schema.
 *
 * @note The backend will only sent the `value` for each option to save bandwidth.
 */
export const zPluginOptionsFrontend = transformSchema(zBasePluginOptions);
export type PluginOptionsFrontend = z.infer<typeof zPluginOptionsFrontend>;

// Window object: PluginAdminConfig
export interface PluginAdminConfig {
	nonce: string;
	nonceTimestamp: string;
	ajaxUrl: string;
	siteName: string;
}

// WC Settings: Window object.

export interface WooCommerceSiteCurrency {
	// The ISO code for the currency.
	code: string;
	// The precision (decimal places).
	precision: number;
	// The symbol for the currency (eg '$')
	symbol: string;
	// The position for the symbol ('left', or 'right')
	symbolPosition: SymbolPosition;
	// The string used for the decimal separator.
	decimalSeparator: string;
	// The string used for the thousands separator.
	thousandSeparator: string;
	// The format string use for displaying an amount in this currency.
	priceFormat: string;
}

export interface WooCommerceSiteLocale {
	// The locale string for the current site.
	siteLocale: string;
	// The locale string for the current user.
	userLocale: string;
	// An array of short weekday strings in the current user's locale.
	weekdaysShort: string[];
}

type wcConfigPage =
	| {
			id: number;
			title: string;
			permalink: null | string;
	  }
	| undefined;

export interface IWooSettings {
	adminUrl: string;
	countries: Countries | never[];
	currency: WooCommerceSiteCurrency;
	currentUserIsAdmin: boolean;
	homeUrl: string;
	displayCartPricesIncludingTax: boolean;
	locale: WooCommerceSiteLocale;
	placeholderImgSrc: string;
	productsSettings: {
		cartRedirectAfterAdd: boolean;
	};
	siteTitle: string;
	storePages: {
		myaccount: wcConfigPage;
		shop: wcConfigPage;
		cart: wcConfigPage;
		checkout: wcConfigPage;
		privacy: wcConfigPage;
		terms: wcConfigPage;
	};
	wcAssetUrl: string;
	wcVersion: string;
	wpLoginUrl: string;
	wpVersion: string;
	shippingCalculatorEnabled: boolean;
	taxesEnabled: boolean;
	couponsEnabled: boolean;
	shippingEnabled: boolean;
	showCartPricesIncTax: boolean;
	taxTotalDisplayItemized: boolean;
	countryTaxOrVat: string;
	endpoints: {
		'order-received': {
			active: boolean;
		};
	};
}
