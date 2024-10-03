import config, { DEFAULT_IGNORES } from "@huntabyte/eslint-config";

const ignores = ["**/vendor"];

export default config({
	svelte: true,
	ignores: [...DEFAULT_IGNORES, ...ignores],
});
