import baseConfig from "@leat/tailwind-config";

/** @type {import('tailwindcss').Config} */
module.exports = {
	content: ["./src/**/*.{html,js,svelte,ts}", "../../packages/**/*.{html,js,svelte,ts}"],
	presets: [baseConfig],
};
