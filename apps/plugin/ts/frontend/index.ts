import { CustomerDashbaord } from '@piggy/plugin-frontend';

const piggyConfig = window.piggyConfig;
const piggyWcSettingsConfig = window.piggyWcSettings;
const piggyMiddlewareConfig = window.piggyMiddlewareConfig;
const frontendMounts = document.getElementsByClassName('piggy-customer-dashboard');

const mountFrontend = () => {
	if (!piggyConfig) {
		console.warn('[piggy] window.piggyConfig is not defined. This is needed to run the app.');
		return;
	}

	if (!piggyWcSettingsConfig) {
		console.warn(
			'[piggy] window.piggyWcSettingsConfig is not defined. This is needed to run the app.'
		);
		return;
	}

	if (!piggyMiddlewareConfig) {
		console.warn(
			'[piggy] window.piggyMiddlewareConfig is not defined. This is needed to run the app.'
		);
		return;
	}

	if (frontendMounts.length > 0) {
		Array.from(frontendMounts).forEach((frontendMount) => {
			new CustomerDashbaord({
				target: frontendMount,
				props: {
					pluginSettings: piggyConfig,
					wcSettings: piggyWcSettingsConfig
				}
			});
		});
	}
};

const app = mountFrontend();

export default app;
