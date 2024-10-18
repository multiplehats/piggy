import { derived, writable } from "svelte/store";
import type { IWooSettings, PluginOptionsFrontend } from "@leat/types/plugin";
import { getTranslatedText } from "$lib/utils/translated-text";

export const currentLanguage = window?.leatMiddlewareConfig?.currentLanguage || "en_US";

export const pluginSettings = writable<PluginOptionsFrontend>();

export const isLoggedIn = window.leatMiddlewareConfig.loggedIn;

export const creditsName = derived(pluginSettings, ($pluginSettings) => {
	return getTranslatedText($pluginSettings.credits_name);
});

// WC Settings

const defaultStorePage = {
	id: 0,
	permalink: null,
	title: "",
};

const initialWcSettingsState: IWooSettings = {
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
	productsSettings: {
		cartRedirectAfterAdd: false,
	},
	placeholderImgSrc: "",
	siteTitle: "",
	storePages: {
		cart: defaultStorePage,
		checkout: defaultStorePage,
		myaccount: defaultStorePage,
		privacy: defaultStorePage,
		shop: defaultStorePage,
		terms: defaultStorePage,
	},
	wcAssetUrl: "",
	wcVersion: "",
	wpLoginUrl: "",
	wpVersion: "",
	taxTotalDisplayItemized: false,
	shippingCalculatorEnabled: true,
	taxesEnabled: true,
	couponsEnabled: true,
	shippingEnabled: true,
	showCartPricesIncTax: true,
	countryTaxOrVat: "tax",
	endpoints: {
		"order-received": {
			active: false,
		},
	},
};

export const wcSettings = writable<IWooSettings>(initialWcSettingsState);

export const locale = derived(wcSettings, ($opt) => {
	return $opt?.locale;
});

export const checkoutUrl = derived(wcSettings, ($opt) => {
	if (!$opt.storePages.checkout?.permalink) {
		return "/checkout";
	}

	return $opt.storePages.checkout.permalink;
});

export const cartUrl = derived(wcSettings, ($opt) => {
	if (!$opt.storePages.cart?.permalink) {
		return "/cart";
	}

	return $opt.storePages.cart.permalink;
});

export const wcPermalinks = derived([checkoutUrl, cartUrl], ([$checkoutUrl, $cartUrl]) => {
	return {
		checkout: $checkoutUrl,

		cart: $cartUrl,
	};
});

export const wcEndpoints = derived(wcSettings, ($opt) => {
	return {
		orderReceived: $opt.endpoints["order-received"],
	};
});

export const wcTaxSettings = derived(wcSettings, ($opt) => {
	const countryTaxOrVat = $opt.countryTaxOrVat;
	const taxesEnabled = $opt.taxesEnabled;
	const showCartPricesIncTax = $opt.showCartPricesIncTax;
	const taxTotalDisplayItemized = $opt.taxTotalDisplayItemized;
	const showCartTax = taxesEnabled && showCartPricesIncTax;

	return {
		countryTaxOrVat,
		showCartTax,
		taxesEnabled,
		showCartPricesIncTax,
		taxTotalDisplayItemized,
	};
});
