import { CustomerDashbaord } from "@leat/plugin-frontend";

const leatConfig = window.leatConfig;
const leatWcSettingsConfig = window.leatWcSettings;
const leatMiddlewareConfig = window.leatMiddlewareConfig;
const frontendMounts = document.getElementsByClassName("leat-customer-dashboard");

function mountFrontend() {
	if (!leatConfig) {
		console.warn("[leat] window.leatConfig is not defined. This is needed to run the app.");
		return;
	}

	if (!leatWcSettingsConfig) {
		console.warn(
			"[leat] window.leatWcSettingsConfig is not defined. This is needed to run the app."
		);
		return;
	}

	if (!leatMiddlewareConfig) {
		console.warn(
			"[leat] window.leatMiddlewareConfig is not defined. This is needed to run the app."
		);
		return;
	}

	if (frontendMounts.length > 0) {
		Array.from(frontendMounts).forEach((frontendMount) => {
			// eslint-disable-next-line no-new
			new CustomerDashbaord({
				target: frontendMount,
				props: {
					pluginSettings: leatConfig,
					wcSettings: leatWcSettingsConfig,
				},
			});
		});
	}
}

const app = mountFrontend();

export default app;
