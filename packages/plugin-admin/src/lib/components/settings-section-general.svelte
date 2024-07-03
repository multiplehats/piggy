<script lang="ts">
	import { createQuery } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import SettingsTranslateableInput from '$lib/components/settings-translateable-input.svelte';
	import { Alert } from '$lib/components/ui/alert';
	import { SettingsSection } from '$lib/components/ui/settings-section';
	import { SettingsAdminService } from '$lib/modules/settings';
	import type { GetSettingsResponse } from '$lib/modules/settings/types';
	import { QueryKeys } from '$lib/utils/query-keys';
	import { noCheckboxSelected } from '$lib/utils/settings-utils';
	import { writable } from 'svelte/store';
	import SettingsCheckboxes from './settings-checkboxes.svelte';
	import SettingsSelect from './settings-select.svelte';
	import SettingsSwitch from './settings-switch.svelte';

	const service = new SettingsAdminService();
	const settings = writable<GetSettingsResponse | null>(null);
	const query = createQuery({
		queryKey: [QueryKeys.settings],
		retry: false,
		queryFn: async () => await service.getAllSettings(),
		refetchOnWindowFocus: true
	});

	$: if ($query.data && $query.isSuccess) {
		settings.set($query.data);
	}
</script>

<SettingsSection title={__('General settings')}>
	<div class="divide-y divide-border w-full">
		{#if $query.isLoading}
			<p>{__('Loading settings')}</p>
		{:else if $query.isError}
			<p>Error: {$query.error.message}</p>
		{:else if $query.isSuccess && $settings}
			<SettingsTranslateableInput
				class="pb-4"
				{...$settings.credits_name}
				bind:value={$settings.credits_name.value}
			/>

			<div class="py-4">
				<SettingsSwitch {...$settings.include_guests} bind:value={$settings.include_guests.value} />

				{#if $settings.include_guests.value === 'off'}
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
			</div>

			<SettingsCheckboxes
				class="py-4"
				{...$settings.reward_order_statuses}
				bind:value={$settings.reward_order_statuses.value}
			/>

			<div class="py-4">
				<SettingsCheckboxes
					{...$settings.withdraw_order_statuses}
					bind:value={$settings.withdraw_order_statuses.value}
				/>

				{#if noCheckboxSelected($settings.withdraw_order_statuses)}
					<Alert
						description={__(
							'If no order status is selected, credits will not be deducted from customers when an order is refunded, partially refunded, or when the payment is voided.',
							'piggy'
						)}
						type="info"
						class="mt-4"
					></Alert>
				{/if}
			</div>

			<SettingsCheckboxes
				class="py-4"
				{...$settings.reward_order_parts}
				bind:value={$settings.reward_order_parts.value}
			/>

			<SettingsSelect
				class="py-4"
				{...$settings.marketing_consent_subscription}
				bind:value={$settings.marketing_consent_subscription.value}
				items={Object.entries($settings.marketing_consent_subscription.options).map(
					([value, { label: name }]) => {
						return {
							value,
							name
						};
					}
				)}
			/>
		{/if}
	</div>
</SettingsSection>
