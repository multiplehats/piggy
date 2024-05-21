<script lang="ts">
	import OnboardingSteps from '$lib/components/onboarding/onboarding-steps.svelte';
	import SettingsLogo from '$lib/components/settings-logo.svelte';
	import {
		currentOnboardingStep,
		initialOnboardingSteps,
		onboardingSteps
	} from '$lib/stores/onboarding';
	import { onMount } from 'svelte';
	import { useNavigate } from 'svelte-navigator';

	$$restProps;

	const navigate = useNavigate();

	onMount(() => {
		currentOnboardingStep.subscribe((step) => {
			if (!step) {
				navigate(initialOnboardingSteps[0].href);

				return;
			}

			navigate(step.href);
		});
	});
</script>

<div class="mx-auto max-w-4xl py-24">
	<div class="bg-white p-8 rounded-lg">
		<div class="w-full items-center justify-center flex mb-8">
			<SettingsLogo />
		</div>

		<OnboardingSteps class="mb-8" />

		{#each $onboardingSteps as { id, component, status } (id)}
			{#if status === 'current' && component}
				<svelte:component this={component} />
			{/if}
		{/each}
	</div>
</div>
