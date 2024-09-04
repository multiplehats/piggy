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
	<div class="divide-y divide-border w-full">
		{#if $query.isLoading}
			<p>{__('Loading settings')}</p>
		{:else if $query.isError}
			<p>Error: {$query.error.message}</p>
		{:else if $query.isSuccess && $settingsState}
			<SettingsTranslateableInput
				class="pb-4"
				{...$settingsState.credits_name}
				bind:value={$settingsState.credits_name.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.credits_spend_rule_progress}
				bind:value={$settingsState.credits_spend_rule_progress.value}
			/>

			<!-- <div class="py-4">
				<SettingsSwitch
					{...$settingsState.include_guests}
					bind:value={$settingsState.include_guests.value}
				/>

				{#if $settingsState.include_guests.value === 'off'}
					<div class="mt-2 text-sm">
						<Alert
							description={__(
								'Only customers who have an account on your store will be included in the loyalty program.',
								'piggy'
							)}
							type="warning"
							class="mt-4"
						></Alert>
					</div>
				{/if}
			</div> -->

			<!-- <SettingsCheckboxes
				class="py-4"
				{...$settingsState.reward_order_statuses}
				bind:value={$settingsState.reward_order_statuses.value}
			/> -->

			<!-- <div class="py-4">
				<SettingsCheckboxes
					{...$settingsState.withdraw_order_statuses}
					bind:value={$settingsState.withdraw_order_statuses.value}
				/>

				{#if noCheckboxSelected($settingsState.withdraw_order_statuses)}
					<Alert
						description={__(
							'If no order status is selected, credits will not be deducted from customers when an order is refunded, partially refunded, or when the payment is voided.',
							'piggy'
						)}
						type="info"
						class="mt-4"
					></Alert>
				{/if}
			</div> -->

			<!-- <SettingsCheckboxes
				class="py-4"
				{...$settingsState.reward_order_parts}
				bind:value={$settingsState.reward_order_parts.value}
			/> -->

			<SettingsSwitch
				class="py-4"
				{...$settingsState.marketing_consent_subscription}
				bind:value={$settingsState.marketing_consent_subscription.value}
			/>
		{/if}
	</div>
</SettingsSection>
