import { get, writable } from "svelte/store";
import type { PluginOptionsAdmin, PluginOptionsAdminKeys } from "@piggy/types";
import { zPluginOptionsAdmin } from "@piggy/types/plugin";
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

/**
 * Saves the settings by calling the API.
 */
export function saveSettings() {
	const settings = get(settingsState);

	const validation = zPluginOptionsAdmin.safeParse(settings);

	if (!validation.success) {
		console.error(validation.error);

		return;
	}

	// From the settings object, create an array of settings to save.
	const settingsToSave = Object.entries(settings).reduce(
		(acc, [key, value]) => {
			acc[key] = value.value;
			return acc;
		},
		{} as Record<string, unknown>
	);
}
