<script lang="ts">
	import SettingsSectionQuickActions from '$lib/components/settings-section-quick-actions.svelte';
	import { Alert } from '$lib/components/ui/alert';
	import { useLocation } from 'svelte-navigator';

	$$restProps;

	const location = useLocation();

	$: onboardingComplete = $location.state?.onboarding === 'complete';
</script>

<div class="grid grid-cols-1 gap-6">
	{#if onboardingComplete}
		<Alert
			type="success"
			title="Onboarding completed"
			description="You have successfully completed the onboarding process. You can now start using Piggy."
		/>
	{/if}
</div>
