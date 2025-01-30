import { join, resolve } from "node:path";
import process from "node:process";
import { v4wp } from "@kucrut/vite-for-wp";
import { wp_scripts } from "@kucrut/vite-for-wp/plugins";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import { visualizer } from "rollup-plugin-visualizer";
import type { PluginOption, UserConfig } from "vite";
import { splitVendorChunkPlugin } from "vite";
import license from "rollup-plugin-license";
import { createBanner } from "./bin/assets-banner";

// const env = process.env.NODE_ENV;
const analyze = process.env.ANALYZE === "true";

const config = {
	build: {
		sourcemap: false,
		emptyOutDir: true,
		minify: false,
		cssMinify: false,
	},
	plugins: [
		v4wp({
			input: "ts/admin/index.ts",
			outDir: "dist/admin",
		}),
		license({
			thirdParty: {
				includePrivate: false,
				includeSelf: false,
				output: {
					file: join(__dirname, "dist/frontend", "dependencies.txt"),
				},
			},
		}) as PluginOption,
		wp_scripts(),
		svelte({
			configFile: resolve(__dirname, "./svelte.config.js"),
		}),
		splitVendorChunkPlugin(),
		// Order matters for banner.
		createBanner("Leat: Admin", "/admin"),
		analyze ? (visualizer({ open: true }) as PluginOption) : null,
	],
} satisfies UserConfig;

export default config;
