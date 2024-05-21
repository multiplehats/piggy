<script lang="ts">
	import { SettingsSection } from '$lib/components/ui/settings-section';
	import { saveSettings, settingsState } from '$lib/stores/settings';
	import Switch from './settings-switch.svelte';

	const onSaved = async () => {
		await saveSettings();
	};

	$: options = [$settingsState.plugin_enable, $settingsState.plugin_reset];
</script>

<SettingsSection title="Quick actions" {onSaved} class="col-span-3">
	<div class="divide-y divide-gray-100">
		{#each options as item (item.id)}
			<div class="py-4 first-of-type:pt-0 last-of-type:pb-0">
				<Switch label={item.label} tooltip={item.tooltip} id={item.id} />
			</div>
		{/each}
	</div>
</SettingsSection>
