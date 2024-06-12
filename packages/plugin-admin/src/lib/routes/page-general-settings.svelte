<script lang="ts">
	import SettingsSectionGeneral from '$lib/components/settings-section-general.svelte';
	import { zPluginOptionsAdmin } from '@piggy/types';

	$$restProps;

	$: result = zPluginOptionsAdmin.safeParse(window.piggySettingsConfig);
	$: if (result?.error) {
		const formatted = result.error.format();
		const errors = formatted._errors;

		console.error('SettingsSectionGeneral', formatted);
	}
</script>

<div class="grid grid-cols-1 gap-6">
	<SettingsSectionGeneral />
</div>
