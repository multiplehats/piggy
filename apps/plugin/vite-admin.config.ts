import { resolve } from "node:path";
import { v4wp } from "@kucrut/vite-for-wp";
import { wp_globals } from "@kucrut/vite-for-wp/utils";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import external_globals from "rollup-plugin-external-globals";
import { visualizer } from "rollup-plugin-visualizer";
import type { UserConfig } from "vite";
import { splitVendorChunkPlugin } from "vite";
import { imagetools } from "vite-imagetools";
import { createBanner } from "./bin/assets-banner";

const env = process.env.NODE_ENV;
const analyze = process.env.ANALYZE === "true";

const config = {
	build: { sourcemap: false },
	plugins: [
		v4wp({
			input: "ts/admin/index.ts",
			outDir: "dist/admin",
		}),
		external_globals(wp_globals()),
		imagetools(),
		svelte({
			configFile: resolve(__dirname, "./svelte.config.js"),
		}),
		splitVendorChunkPlugin(),
		// Order matters for banner.
		// @ts-expect-error - Doesn't have the right types.
		createBanner("Piggy: Admin", "/admin"),
		...(analyze ? [visualizer({ open: true })] : []),
	],
} satisfies UserConfig;

export default config;
