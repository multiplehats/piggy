import { vitePreprocess } from "@sveltejs/vite-plugin-svelte";

/** @type {import('@sveltejs/kit').Config} */
const config = {
	preprocess: vitePreprocess(),
	kit: {
		typescript: {
			config: (config) => {
				return {
					...config,
					compilerOptions: {
						...config.compilerOptions,
						types: ["jquery"],
					},
					include: [
						...config.include,
						// This is important, it includes the window object amongst other things
						// References the @piggy/types package directly
						"../../types/plugin/augment.d.ts",
					],
				};
			},
		},
	},
};

export default config;
