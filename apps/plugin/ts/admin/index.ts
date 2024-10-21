import { Admin } from "@leat/plugin-admin";

const leatWcSettingsConfig = window.leatWcSettings;
const adminMount = document.getElementById("leat-admin-mount");

function mountFrontend() {
	if (adminMount) {
		if (!leatWcSettingsConfig) {
			console.warn(
				"[leat] window.leatWcSettingsConfig is not defined. This is needed to run the app."
			);
			return;
		}

		// eslint-disable-next-line no-new
		new Admin({
			target: adminMount,
		});
	} else {
		console.info("[leat] Could not find Leat element to mount on.");
	}
}

const app = mountFrontend();

export default app;
