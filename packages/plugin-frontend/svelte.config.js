import { vitePreprocess } from "@sveltejs/vite-plugin-svelte";

/** @type {import('@sveltejs/kit').Config} */
const config = {
	preprocess: vitePreprocess(),
	kit: {
		typescript: {
			config: (config) => {
				config.include.push("../../../packages/types/plugin/augment.d.ts");

				// Ensure config.compilerOptions exists
				config.compilerOptions = config.compilerOptions || {};
				// Ensure config.compilerOptions.types exists as an array
				config.compilerOptions.types = config.compilerOptions.types || [];
				// Push "jquery" to the types array
				config.compilerOptions.types.push("jquery");

				return config;
			},
		},
	},
};

export default config;
