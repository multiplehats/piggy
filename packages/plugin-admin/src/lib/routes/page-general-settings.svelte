<script lang="ts">
	import { createMutation, useQueryClient } from '@tanstack/svelte-query';
	import SettingsFormActions from '$lib/components/settings-form-actions.svelte';
	import SettingsSectionGeneral from '$lib/components/settings-section-general.svelte';
	import { saveSettingsMutationConfig } from '$lib/modules/settings/mutations';
	import { settingsState } from '$lib/stores/settings';

	$$restProps;

	const client = useQueryClient();
	const saveSettingsMutation = createMutation(saveSettingsMutationConfig(client));

	function handleSubmit(
		e: SubmitEvent & {
			currentTarget: EventTarget & HTMLFormElement;
		}
	) {
		e.preventDefault();

		$saveSettingsMutation.mutateAsync(settingsState);
	}
</script>

<form method="POST" on:submit={handleSubmit}>
	<div class="grid grid-cols-1 gap-3">
		<SettingsSectionGeneral />

		<SettingsFormActions saving={$saveSettingsMutation.isPending} />
	</div>
</form>
