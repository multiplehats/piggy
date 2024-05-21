<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import type { FormInputEvent } from '$lib/components/ui/input';
	import { SettingsSection } from '$lib/components/ui/settings-section';
	import { saveSettings, settingsState } from '$lib/stores/settings';
	import SettingsInput from './settings-input.svelte';

	const onSaved = async () => {
		await saveSettings();
	};

	// Create a function that handles the on change, to only update the state when the first 6 characters are different than the current value.
	const handleOnChange = (e: FormInputEvent<Event>) => {
		e.preventDefault();

		const value = e.currentTarget.value;

		if (value.length > 6 && value.slice(0, 6) === $settingsState.api_key.value.slice(0, 6)) {
			console.log('Value is the same');

			return;
		}

		$settingsState.api_key.value = value;
	};
</script>

<SettingsSection title="General settings" {onSaved} class="col-span-3">
	<SettingsInput
		{...$settingsState.api_key}
		class="font-mono"
		value={$settingsState.api_key.value}
		on:change={handleOnChange}
	/>
</SettingsSection>
