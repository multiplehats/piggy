import type { Callback } from "@wordpress/hooks";

/**
 * @note: Hook names can only contain numbers, letters, dashes, periods and underscores.
 */
type ActionHookName = "on.init";

const _ACTION_HOOK_NAMES: Record<ActionHookName, `on.${string}` | `do.${string}`> = {
	"on.init": "on.init",
} as const;

type ActionHookNames = keyof typeof _ACTION_HOOK_NAMES;

const NAMESPACE = "leat";

export const hooks = {
	doAction: (hookname: ActionHookNames, ...args: unknown[]) => {
		window.wp.hooks.doAction(hookname, ...args);
	},

	addAction: (hookname: ActionHookNames, callback: Callback, priority?: number) => {
		window.wp.hooks.addAction(hookname, NAMESPACE, callback, priority);
	},

	doingAction: (hookname: ActionHookNames) => {
		return window.wp.hooks.doingAction(hookname);
	},
};
