<script lang="ts">
	import { createMutation, useQueryClient } from "@tanstack/svelte-query";
	import SettingsFormActions from "$lib/components/settings-form-actions.svelte";
	import SettingsSectionDashboard from "$lib/components/settings-section-dashboard.svelte";
	import { saveSettingsMutationConfig } from "$lib/modules/settings/mutations";
	import { settingsState } from "$lib/stores/settings";

	// eslint-disable-next-line no-unused-expressions
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

<form
	class="relative min-h-[calc(100vh-var(--header-height))] pb-24"
	method="POST"
	on:submit={handleSubmit}
>
	<div class="grid grid-cols-1 gap-3">
		<SettingsSectionDashboard />

		<SettingsFormActions saving={$saveSettingsMutation.isPending} />
	</div>
</form>
