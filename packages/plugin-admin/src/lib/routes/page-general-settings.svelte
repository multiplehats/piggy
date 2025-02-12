<script lang="ts">
	import { createMutation, useQueryClient } from "@tanstack/svelte-query";
	import { useLocation } from "svelte-navigator";
	import { __ } from "@wordpress/i18n";
	import SettingsFormActions from "$lib/components/settings-form-actions.svelte";
	import SettingsSectionGeneral from "$lib/components/settings-section-general.svelte";
	import { Alert } from "$lib/components/ui/alert";
	import { saveSettingsMutationConfig } from "$lib/modules/settings/mutations";
	import { settingsState } from "$lib/stores/settings";

	// eslint-disable-next-line no-unused-expressions
	$$restProps;

	const location = useLocation();
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

	$: onboardingComplete = $location.state?.onboarding === "complete";
</script>

<form class="relative" method="POST" on:submit={handleSubmit}>
	<div class="grid grid-cols-1 gap-3">
		{#if onboardingComplete}
			<Alert
				type="info"
				title={__("You're all set!")}
				description={__(
					"You have successfully completed the onboarding process. You can now start using Leat."
				)}
			/>
		{/if}

		<SettingsSectionGeneral />

		<SettingsFormActions saving={$saveSettingsMutation.isPending} />
	</div>
</form>
