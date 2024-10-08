<script lang="ts">
	import type { Color } from "@piggy/types/plugin/settings/adminTypes";
	import ColorPicker from "./settings-color-picker.svelte";
	import { SettingsSection } from "$lib/components/ui/settings-section";

	export let colorList: Color[];
</script>

<SettingsSection class="col-span-3">
	<div class="grid grid-cols-3 gap-4">
		{#each colorList as item (item.id)}
			<ColorPicker {...item} />
		{/each}
	</div>
</SettingsSection>
