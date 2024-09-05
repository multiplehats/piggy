<script lang="ts">
	import { createQuery } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import SettingsTranslateableInput from '$lib/components/settings-translateable-input.svelte';
	import { Alert } from '$lib/components/ui/alert';
	import { SettingsSection } from '$lib/components/ui/settings-section';
	import { SettingsAdminService } from '$lib/modules/settings';
	import { settingsState } from '$lib/stores/settings';
	import { QueryKeys } from '$lib/utils/query-keys';
	import { noCheckboxSelected } from '$lib/utils/settings-utils';
	import SettingsApiKey from './settings-api-key/settings-api-key.svelte';
	import SettingsCheckboxes from './settings-checkboxes.svelte';
	import SettingsSelect from './settings-select.svelte';
	import SettingsSwitch from './settings-switch.svelte';

	const service = new SettingsAdminService();
	const query = createQuery({
		queryKey: [QueryKeys.settings],
		retry: false,
		queryFn: async () => await service.getAllSettings(),
		refetchOnWindowFocus: true
	});

	$: if ($query.data && $query.isSuccess) {
		settingsState.set($query.data);
	}
</script>

<SettingsSection title={__('General settings')}>
	{#if $query.isLoading}
		<p>{__('Loading settings')}</p>
	{:else if $query.isError}
		<p>Error: {$query.error.message}</p>
	{:else if $query.isSuccess && $settingsState}
		<div class="divide-y max-w-md divide-border w-full">
			<SettingsTranslateableInput
				class="pb-4"
				{...$settingsState.dashboard_title_logged_in}
				bind:value={$settingsState.dashboard_title_logged_in.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_title_logged_out}
				bind:value={$settingsState.dashboard_title_logged_out.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_join_cta}
				bind:value={$settingsState.dashboard_join_cta.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_login_cta}
				bind:value={$settingsState.dashboard_login_cta.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_nav_coupons}
				bind:value={$settingsState.dashboard_nav_coupons.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_nav_earn}
				bind:value={$settingsState.dashboard_nav_earn.value}
			/>
			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_nav_rewards}
				bind:value={$settingsState.dashboard_nav_rewards.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_nav_activity}
				bind:value={$settingsState.dashboard_nav_activity.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_earn_cta}
				bind:value={$settingsState.dashboard_earn_cta.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.dashboard_spend_cta}
				bind:value={$settingsState.dashboard_spend_cta.value}
			/>
		</div>
	{/if}
</SettingsSection>
