<script lang="ts">
	import { createMutation, useQueryClient } from '@tanstack/svelte-query';
	import OnboardingActions from '$lib/components/onboarding/onboarding-actions.svelte';
	import OnboardingSteps from '$lib/components/onboarding/onboarding-steps.svelte';
	import SettingsLogo from '$lib/components/settings-logo.svelte';
	import { saveSettingsMutationConfig } from '$lib/modules/piggy/mutations';
	import { onboardingSteps } from '$lib/stores/onboarding';
	import { saveSettings, settingsState } from '$lib/stores/settings';

	$$restProps;

	const client = useQueryClient();
	const saveSettingsMutation = createMutation(saveSettingsMutationConfig(client));

	function handleSubmit(
		e: SubmitEvent & {
			currentTarget: EventTarget & HTMLFormElement;
		}
	) {
		e.preventDefault();

		const form = e.currentTarget;
		const formData = new FormData(form);

		// Log through the form data
		for (const [key, value] of formData.entries()) {
			console.log(`${key}: ${value}`);
		}

		const settingsToSave = Object.entries($settingsState).reduce(
			(acc, [key, value]) => {
				acc[key] = value.value;
				return acc;
			},
			{} as Record<string, unknown>
		);

		$saveSettingsMutation.mutate(settingsToSave);

		// saveSettings();
	}
</script>

<div class="mx-auto max-w-4xl py-24">
	<div class="p-8 rounded-lg border">
		<div class="w-full items-center justify-center flex mb-8">
			<SettingsLogo />
		</div>

		<OnboardingSteps class="mb-8" />

		<form method="POST" on:submit={handleSubmit}>
			{#each $onboardingSteps as { id, component, status, showActions } (id)}
				{#if status === 'current' && component}
					<svelte:component this={component} />

					{#if showActions}
						<OnboardingActions />
					{/if}
				{/if}
			{/each}
		</form>
	</div>
</div>
