<script lang="ts">
	import { createMutation, useQueryClient } from "@tanstack/svelte-query";
	import SettingsFormActions from "$lib/components/settings-form-actions.svelte";
	import { saveSettingsMutationConfig } from "$lib/modules/settings/mutations";
	import { settingsState } from "$lib/stores/settings";
	import SettingsSectionGiftcard from "$lib/components/settings-section-giftcard.svelte";

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

<form class="relative" method="POST" on:submit={handleSubmit}>
	<div class="grid grid-cols-1 gap-3">
		<SettingsSectionGiftcard />

		<SettingsFormActions saving={$saveSettingsMutation.isPending} />
	</div>
</form>
