<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import SettingsSection from "../ui/settings-section/settings-section.svelte";
	import { SettingsApiKey } from "$lib/components/settings-api-key/index.js";
	import { OnboardingStepId, useOnboarding } from "$lib/stores/onboarding";

	const onboarding = useOnboarding();

	let initialising = false;

	$: {
		onboarding.setInitialising(OnboardingStepId.connectAccount, initialising);
	}
</script>

<SettingsSection title={__("Connect to Leat", "leat")}>
	<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
		<SettingsApiKey bind:isLoading={initialising} />
	</div>
</SettingsSection>
