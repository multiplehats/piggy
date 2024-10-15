import type { Hooks } from "@wordpress/hooks";
import type { IWooSettings, PluginOptionsFrontend } from "./settings";
import type { SpendRuleValueItem } from "./settings/adminTypes";

declare global {
	// eslint-disable-next-line ts/consistent-type-definitions
	interface Window {
		// Frontend settings
		piggyConfig: PluginOptionsFrontend;
		// WooCommerce settings
		piggyWcSettings: IWooSettings;
		piggySpentRules: SpendRuleValueItem[] | null;

		// WP Fetch middleware config
		piggyMiddlewareConfig: {
			apiKeySet: boolean;
			userId: number | null;
			loggedIn: boolean;
			siteLanguage: string;
			languages: string[];
			currentLanguage: string;
			storeApiNonce: string;
			wcStoreApiNonceTimestamp: string;
		};
		wp: {
			hooks: Hooks;
			[other: string]: unknown;
		};
	}
}

export {};
