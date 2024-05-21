<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import SettingsInput from '$lib/components/settings-input.svelte';
	import type { FormInputEvent } from '$lib/components/ui/input';
	import { settingsState } from '$lib/stores/settings';
	import ComboboxPiggyShop from '../combobox-piggy-shop.svelte';

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

<SettingsInput
	{...$settingsState.api_key}
	class="font-mono"
	value={$settingsState.api_key.value}
	on:change={handleOnChange}
/>

<ComboboxPiggyShop />
