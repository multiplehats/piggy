import type { Hooks } from "@wordpress/hooks";
import type { IWooSettings, PluginOptionsFrontend } from "./settings";
import type { SpendRuleValueItem } from "./settings/adminTypes";

declare global {
	// eslint-disable-next-line ts/consistent-type-definitions
	interface Window {
		// Frontend settings
		leatConfig: PluginOptionsFrontend;
		// WooCommerce settings
		leatWcSettings: IWooSettings;
		leatSpentRules: SpendRuleValueItem[] | null;
		leatGiftCardConfig: {
			nonce: string;
			ajaxUrl: string;
			checkingText: string;
			balanceText: string;
			errorText: string;
		};
		// WP Fetch middleware config
		leatMiddlewareConfig: {
			apiKeySet: boolean;
			userId: number | null;
			loggedIn: boolean;
			siteLanguage: string;
			languages: string[];
			currentLanguage: string;
			storeApiNonce: string;
			wcStoreApiNonceTimestamp: string;
			wpApiNonce: string;
		};
		wp: {
			hooks: Hooks;
			[other: string]: unknown;
		};
	}
}

export {};
