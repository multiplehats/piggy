<script lang="ts">
	import { createMutation, useQueryClient } from '@tanstack/svelte-query';
	import OnboardingActions from '$lib/components/onboarding/onboarding-actions.svelte';
	import OnboardingSteps from '$lib/components/onboarding/onboarding-steps.svelte';
	import SettingsLogo from '$lib/components/settings-logo.svelte';
	import { saveSettingsMutationConfig } from '$lib/modules/settings/mutations';
	import { onboardingSteps, useOnboarding } from '$lib/stores/onboarding';
	import { settingsState } from '$lib/stores/settings';
	import { useNavigate } from 'svelte-navigator';

	$$restProps;

	const navigate = useNavigate();
	const onboarding = useOnboarding();
	const client = useQueryClient();
	const saveSettingsMutation = createMutation(
		saveSettingsMutationConfig(client, {
			onSuccess: () => {
				const isLastStep = onboarding.isLastStep();

				if (isLastStep) {
					navigate('/', {
						state: {
							onboarding: 'complete'
						}
					});
					return;
				}

				const { href } = onboarding.nextStep();

				navigate(href);
			}
		})
	);

	function handleSubmit(
		e: SubmitEvent & {
			currentTarget: EventTarget & HTMLFormElement;
		}
	) {
		e.preventDefault();

		$saveSettingsMutation.mutateAsync(settingsState);
	}
</script>

<div class="mx-auto max-w-4xl py-24">
	<div class="p-8 rounded-lg border">
		<div class="w-full items-center justify-center flex mb-8">
			<SettingsLogo />
		</div>

		<OnboardingSteps class="mb-8" />

		<form method="POST" on:submit={handleSubmit}>
			{#each $onboardingSteps as { id, component, status, showActions, initialising } (id)}
				{#if status === 'current'}
					{#if component}
						<svelte:component this={component} />
					{:else}
						<p>Onboarding component for step {id} not found.</p>
					{/if}

					{#if showActions}
						<OnboardingActions saving={$saveSettingsMutation.isPending} disabled={initialising} />
					{/if}
				{/if}
			{/each}
		</form>
	</div>
</div>
