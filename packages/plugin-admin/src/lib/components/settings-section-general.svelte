<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import SettingsTranslateableInput from '$lib/components/settings-translateable-input.svelte';
	import { Alert } from '$lib/components/ui/alert';
	import { SettingsSection } from '$lib/components/ui/settings-section';
	import { settingsState } from '$lib/stores/settings';
	import { noCheckboxSelected } from '$lib/utils/settings-utils';
	import SettingsCheckboxes from './settings-checkboxes.svelte';
	import SettingsSwitch from './settings-switch.svelte';
</script>

<SettingsSection title={__('General settings')}>
	<div class="divide-y divide-border w-full">
		<SettingsTranslateableInput
			class="pb-4"
			{...$settingsState.credits_name}
			bind:value={$settingsState.credits_name.value}
		/>

		<div class="py-4">
			<SettingsSwitch
				{...$settingsState.include_guests}
				bind:value={$settingsState.include_guests.value}
			/>

			{#if $settingsState.include_guests.value === 'off'}
				<div class="mt-2 text-sm">
					{__(
						'Only customers who have an account on your store will be included in the loyalty program.',
						'piggy'
					)}
				</div>
			{/if}
		</div>

		<SettingsCheckboxes
			class="py-4"
			{...$settingsState.reward_order_statuses}
			bind:value={$settingsState.reward_order_statuses.value}
		/>

		<div class="py-4">
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
		</div>

		<SettingsCheckboxes
			class="py-4"
			{...$settingsState.reward_order_parts}
			bind:value={$settingsState.reward_order_parts.value}
		/>
	</div>
</SettingsSection>
