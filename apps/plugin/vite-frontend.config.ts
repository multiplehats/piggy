import { resolve } from "node:path";
import process from "node:process";
import { v4wp } from "@kucrut/vite-for-wp";
import { wp_globals } from "@kucrut/vite-for-wp/utils";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import external_globals from "rollup-plugin-external-globals";
import { visualizer } from "rollup-plugin-visualizer";
import { type UserConfig, splitVendorChunkPlugin } from "vite";
import { createBanner } from "./bin/assets-banner";

// const env = process.env.NODE_ENV;
const analyze = process.env.ANALYZE === "true";

const config = {
	build: {
		sourcemap: false,
		rollupOptions: {
			output: {
				manualChunks(id: string) {
					if (id.includes("dinero")) {
						return "dinero";
					}
				},
			},
		},
	},
	plugins: [
		v4wp({
			input: "ts/frontend/index.ts",
			outDir: "dist/frontend",
		}),
		external_globals(wp_globals()),
		svelte({
			configFile: resolve(__dirname, "./svelte.config.js"),
		}),
		splitVendorChunkPlugin(),
		// Order matters for banner.
		// @ts-expect-error - Doesn't have the right types.
		createBanner("Leat: Frontend", "/frontend"),
		...(analyze ? [visualizer({ open: true })] : []),
	],
} satisfies UserConfig;

export default config;
