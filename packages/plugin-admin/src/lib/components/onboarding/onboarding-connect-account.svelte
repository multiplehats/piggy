<script lang="ts">
	import { SettingsApiKey } from '$lib/components/settings-api-key/index.js';
	import { OnboardingStepId, useOnboarding } from '$lib/stores/onboarding';

	const onboarding = useOnboarding();

	let initialising = false;

	$: {
		onboarding.setInitialising(OnboardingStepId.connectAccount, initialising);
	}
</script>

<SettingsApiKey bind:isLoading={initialising} />
