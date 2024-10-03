<script lang="ts">
	import Switch from "./settings-switch.svelte";
	import { SettingsSection } from "$lib/components/ui/settings-section";
	import { settingsState } from "$lib/stores/settings";

	$: options = [$settingsState.plugin_enable, $settingsState.plugin_reset];
</script>

<SettingsSection title="Quick actions" class="col-span-3">
	<div class="divide-y divide-gray-100">
		{#each options as item (item.id)}
			<div class="py-4 first-of-type:pt-0 last-of-type:pb-0">
				<Switch label={item.label} tooltip={item.tooltip} id={item.id} value={item.value} />
			</div>
		{/each}
	</div>
</SettingsSection>
