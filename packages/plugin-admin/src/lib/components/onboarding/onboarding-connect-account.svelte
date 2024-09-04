<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import { SettingsApiKey } from '$lib/components/settings-api-key/index.js';
	import { OnboardingStepId, useOnboarding } from '$lib/stores/onboarding';
	import SettingsSection from '../ui/settings-section/settings-section.svelte';

	const onboarding = useOnboarding();

	let initialising = false;

	$: {
		onboarding.setInitialising(OnboardingStepId.connectAccount, initialising);
	}
</script>

<SettingsSection title={__('Connect to Piggy', 'piggy')}>
	<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
		<SettingsApiKey bind:isLoading={initialising} />
	</div>
</SettingsSection>
