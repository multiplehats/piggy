import type { Hooks } from '@wordpress/hooks';
import type {
	IWooSettings,
	PluginAdminConfig,
	PluginOptionsAdmin,
	PluginOptionsFrontend
} from './settings';

declare global {
	interface Window {
		// Frontend settings
		piggyConfig: PluginOptionsFrontend;
		// WooCommerce settings
		piggyWcSettings: IWooSettings;
		// WP Fetch middleware config
		piggyMiddlewareConfig: {
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
