<script lang="ts">
	import { useLocation } from "svelte-navigator";
	import { __ } from "@wordpress/i18n";
	import { Alert } from "$lib/components/ui/alert";

	// eslint-disable-next-line no-unused-expressions
	$$restProps;

	const location = useLocation();

	$: onboardingComplete = $location.state?.onboarding === "complete";
</script>

<div class="grid grid-cols-1 gap-6">
	{#if onboardingComplete}
		<Alert
			type="success"
			title={__("Onboarding completed")}
			description={__("You have successfully completed the onboarding process. You can now start using Leat.")}
		/>
	{/if}
</div>
