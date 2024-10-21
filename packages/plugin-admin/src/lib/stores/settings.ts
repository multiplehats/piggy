import { get, writable } from "svelte/store";
import type { PluginOptionsAdmin, PluginOptionsAdminKeys } from "@leat/types";
import type { GetSettingsResponse } from "$lib/modules/settings/types";

// Settings State

export const settingsState = writable<GetSettingsResponse>();

// ACTIONS

export function updateSettings({
	id,
	value,
	onComplete,
}: {
	id: string;
	value: unknown;
	onComplete?: ({ settings }: { settings: typeof settingsState }) => void;
}) {
	const currentSettings = get(settingsState);
	const savedOption = currentSettings[id as PluginOptionsAdminKeys];

	if (!savedOption) {
		return;
	}

	settingsState.update((state) => {
		const newState = {
			...state,
			[id]: {
				...state[id as keyof PluginOptionsAdmin],
				value,
			},
		};

		return newState;
	});

	if (onComplete) {
		onComplete({
			settings: settingsState,
		});
	}
}
