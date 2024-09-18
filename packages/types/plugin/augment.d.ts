import type { Hooks } from '@wordpress/hooks';
import type {
	IWooSettings,
	PluginAdminConfig,
	PluginOptionsAdmin,
	PluginOptionsFrontend
} from './settings';
import { EarnRuleValueItem, SpendRuleValueItem } from './settings/adminTypes';

declare global {
	interface Window {
		// Frontend settings
		piggyConfig: PluginOptionsFrontend;
		// WooCommerce settings
		piggyWcSettings: IWooSettings;
		piggySpentRules: SpendRuleValueItem[] | null;

		// WP Fetch middleware config
		piggyMiddlewareConfig: {
			apiKeySet: boolean;
			userId: number;
			loggedIn: boolean;
			siteLanguage: string;
			languages: string[];
			currentLanguage: string;
			storeApiNonce: string;
			wcStoreApiNonceTimestamp: string;
		};
		// Admin config
		// piggyAdminConfig: PluginAdminConfig;
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		wp: {
			hooks: Hooks;
			[other: string]: unknown;
		};
	}
}

export {};
