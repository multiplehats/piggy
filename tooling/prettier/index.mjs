import { fileURLToPath } from 'url';

/** @typedef  {import("prettier").Config} PrettierConfig */
/** @typedef {import("prettier-plugin-tailwindcss").PluginOptions} TailwindConfig */
/** @typedef  {import("@ianvs/prettier-plugin-sort-imports").PluginConfig} SortImportsConfig */

/** @type { PrettierConfig | SortImportsConfig | TailwindConfig } */
const config = {
	plugins: ['@ianvs/prettier-plugin-sort-imports', 'prettier-plugin-tailwindcss'],
	tailwindConfig: fileURLToPath(new URL('../../tooling/tailwind/index.ts', import.meta.url)),
	useTabs: true,
	singleQuote: true,
	trailingComma: 'none',
	printWidth: 100,
	importOrder: [
		'^\\./\\$types',
		'^\\$app/(.*)$',
		'^\\$env/(.*)$',
		'^@sveltejs/kit(.*)$',
		'<THIRD_PARTY_MODULES>',
		'^@piggy/(.*)$',
		'^~/',
		'^[../]',
		'^[./]'
	],
	importOrderParserPlugins: ['typescript'],
	importOrderTypeScriptVersion: '4.4.0'
};

export default config;
