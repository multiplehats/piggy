import { Frontend } from '@piggy/plugin-frontend';

const piggyConfig = window.piggyConfig;
const piggyWcSettingsConfig = window.piggyWcSettings;
const piggyMiddlewareConfig = window.piggyMiddlewareConfig;
const frontendMount = document.getElementById('piggy-frontend-mount');

const mountFrontend = () => {
	if (frontendMount) {
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

		// eslint-disable-next-line @typescript-eslint/no-unsafe-call
		new Frontend({
			target: frontendMount,
			props: {
				pluginSettings: piggyConfig,
				wcSettings: piggyWcSettingsConfig
			}
		});
	} else {
		console.info('[piggy] Could not find Piggy element to mount on.');
		return;
	}
};

const app = mountFrontend();

export default app;
