import { __, sprintf } from '@wordpress/i18n';
import { api } from '$lib/config/api';
import toast from 'svelte-french-toast';
import { get, writable } from 'svelte/store';
import type { PluginAdminConfig, PluginOptionsAdmin, PluginOptionsAdminKeys } from '@piggy/types';
import { zPluginOptionsAdmin } from '@piggy/types/plugin';

// Settings State

export const settingsState = writable<PluginOptionsAdmin>();
export const adminConfigState = writable<PluginAdminConfig>();

// ACTIONS

export const updateSettings = ({
	id,
	value,
	onComplete
}: {
	id: string;
	value: unknown;
	onComplete?: ({ settings }: { settings: typeof settingsState }) => void;
}) => {
	const currentSettings = get(settingsState);
	const savedOption = currentSettings[id as PluginOptionsAdminKeys];

	if (!savedOption) {
		toast.error(sprintf(__("Option '%s' not found in the settings.", 'piggy'), id));
		return;
	}

	settingsState.update((state) => {
		const newState = {
			...state,
			[id]: {
				...state[id as keyof PluginOptionsAdmin],
				value
			}
		};

		return newState;
	});

	if (onComplete) {
		onComplete({
			settings: settingsState
		});
	}
};

/**
 * Saves the settings by calling the API.
 */
export const saveSettings = async () => {
	const settings = get(settingsState);
	const validation = zPluginOptionsAdmin.safeParse(settings);

	if (!validation.success) {
		toast.error(__('Error saving settings.', 'piggy'));

		console.error(validation.error);

		return;
	}

	return toast.promise(
		api
			.post<PluginOptionsAdmin>('save_options', {
				settings: get(settingsState)
			})
			.then((response) => {
				if (!response.data) {
					throw new Error('No data returned');
				}
				return response.data;
			}),
		{
			loading: __('Saving settings...', 'piggy'),
			success: __('Settings saved.', 'piggy'),
			error: __('Error saving settings.', 'piggy')
		}
	);
};
