import { Admin } from '@piggy/plugin-admin';

const piggyWcSettingsConfig = window.piggyWcSettings;
const adminMount = document.getElementById('piggy-admin-mount');

const mountFrontend = () => {
	if (adminMount) {
		if (!piggyWcSettingsConfig) {
			console.warn(
				'[piggy] window.piggyWcSettingsConfig is not defined. This is needed to run the app.'
			);
			return;
		}

		new Admin({
			target: adminMount
		});
	} else {
		console.info('[piggy] Could not find Piggy element to mount on.');
		return;
	}
};

const app = mountFrontend();

export default app;
