<script lang="ts">
	import type { Color } from '@piggy/types/plugin/settings/adminTypes';
	import { __ } from '@wordpress/i18n';
	import { SettingsSection } from '$lib/components/ui/settings-section';
	import { saveSettings } from '$lib/stores/settings';
	import ColorPicker from './settings-color-picker.svelte';

	const onSaved = async () => {
		await saveSettings();
	};

	export let colorList: Color[];
</script>

<SettingsSection {onSaved} class="col-span-3">
	<div class="grid grid-cols-3 gap-4">
		{#each colorList as item (item.id)}
			<ColorPicker {...item} />
		{/each}
	</div>
</SettingsSection>
