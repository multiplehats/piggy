import type { WooCommerceSiteCurrency } from "@piggy/types";

export type WooCommerceSiteLocale = {
	// The locale string for the current site.
	siteLocale: string;
	// The locale string for the current user.
	userLocale: string;
	// An array of short weekday strings in the current user's locale.
	weekdaysShort: string[];
};

export type WooCommerceSharedSettings = {
	adminUrl: string;
	countries: Record<string, string> | never[];
	currency: WooCommerceSiteCurrency;
	currentUserIsAdmin: boolean;
	homeUrl: string;
	displayCartPricesIncludingTax: boolean;
	locale: WooCommerceSiteLocale;
	orderStatuses: Record<string, string> | never[];
	placeholderImgSrc: string;
	siteTitle: string;
	storePages: Record<string, string> | never[];
	wcAssetUrl: string;
	wcVersion: string;
	wpLoginUrl: string;
	wpVersion: string;
};
type WooCommerceSharedSettingsKeys = keyof WooCommerceSharedSettings;

const defaults: WooCommerceSharedSettings = {
	adminUrl: "",
	countries: [],
	displayCartPricesIncludingTax: false,
	currency: {
		code: "USD",
		precision: 2,
		symbol: "$",
		symbolPosition: "left",
		decimalSeparator: ".",
		priceFormat: "%1$s%2$s",
		thousandSeparator: ",",
	},
	currentUserIsAdmin: false,
	homeUrl: "",
	locale: {
		siteLocale: "en_US",
		userLocale: "en_US",
		weekdaysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
	},
	orderStatuses: [],
	placeholderImgSrc: "",
	siteTitle: "",
	storePages: [],
	wcAssetUrl: "",
	wcVersion: "",
	wpLoginUrl: "",
	wpVersion: "",
};

const globalSharedSettings =
	typeof window.piggyWcSettings === "object" ? window.piggyWcSettings : {};

// Use defaults or global settings, depending on what is set.
const allWcSettings: WooCommerceSharedSettings = {
	...defaults,
	...globalSharedSettings,
};

allWcSettings.currency = {
	...defaults.currency,
	...allWcSettings.currency,
};

allWcSettings.locale = {
	...defaults.locale,
	...allWcSettings.locale,
};

/**
 * Retrieves a setting value from the setting state.
 *
 * If a setting with key `name` does not exist or is undefined,
 * the `fallback` will be returned instead. An optional `filter`
 * callback can be passed to format the returned value.
 */
export function getSetting<T>(
	name: string,
	fallback: unknown = false,
	filter = (val: unknown, fb: unknown) => (typeof val !== "undefined" ? val : fb)
): T {
	const value =
		name in allWcSettings ? allWcSettings[name as WooCommerceSharedSettingsKeys] : fallback;
	return filter(value, fallback) as T;
}

export function getSettingWithCoercion<T>(
	name: string,
	fallback: T,
	typeguard: (val: unknown, fb: unknown) => val is T
): T {
	const value =
		name in allWcSettings ? allWcSettings[name as WooCommerceSharedSettingsKeys] : fallback;
	return typeguard(value, fallback) ? value : fallback;
}

export { allWcSettings };
