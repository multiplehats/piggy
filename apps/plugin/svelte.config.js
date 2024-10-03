import sveltePreprocess from "svelte-preprocess";

export default {
	// Consult https://github.com/sveltejs/svelte-preprocess
	// for more information about preprocessors
	preprocess: sveltePreprocess({
		postcss: true,
		sourceMap: process.env.NODE_ENV !== "production",
	}),
	vitePlugin: {
		inspector: {
			toggleKeyCombo: "control-shift",
			holdMode: true,
			showToggleButton: "never",
		},
	},
};
