<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import SettingsApiKey from "./settings-api-key/settings-api-key.svelte";
	import SettingsSwitch from "./settings-switch.svelte";
	import { Alert } from "./ui/alert";
	import SettingsSelect from "./settings-select.svelte";
	import SettingsCheckboxes from "./settings-checkboxes.svelte";

	import SettingsTranslateableInput from "$lib/components/settings-translateable-input.svelte";
	import { SettingsSection } from "$lib/components/ui/settings-section";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { settingsState } from "$lib/stores/settings";
	import { QueryKeys } from "$lib/utils/query-keys";

	const service = new SettingsAdminService();
	const query = createQuery({
		queryKey: [QueryKeys.settings],
		retry: false,
		queryFn: async () => await service.getAllSettings(),
		refetchOnWindowFocus: true,
	});

	$: if ($query.data && $query.isSuccess) {
		settingsState.set($query.data);
	}
</script>

<SettingsSection title={__("General settings")}>
	<div class="divide-border w-full divide-y">
		{#if $query.isLoading}
			<p>{__("Loading settings")}</p>
		{:else if $query.isError}
			<p>Error: {$query.error.message}</p>
		{:else if $query.isSuccess && $settingsState}
			<div class="grid grid-cols-1 gap-y-2 pb-4">
				<SettingsApiKey />
			</div>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.credits_name}
				bind:value={$settingsState.credits_name.value}
			/>

			<SettingsTranslateableInput
				class="py-4"
				{...$settingsState.credits_spend_rule_progress}
				bind:value={$settingsState.credits_spend_rule_progress.value}
			/>

			<div class="py-4">
				<SettingsSwitch
					{...$settingsState.include_guests}
					bind:value={$settingsState.include_guests.value}
				/>

				{#if $settingsState.include_guests.value === "off"}
					<div class="mt-2 text-sm">
						<Alert
							description={__(
								"Only customers who have an account on your store will be included in the loyalty program.",
								"leat-crm"
							)}
							type="warning"
							class="mt-4"
						></Alert>
					</div>
				{/if}
			</div>

			<div class="py-4">
				<SettingsSelect
					class="pb-4"
					{...$settingsState.reward_order_statuses}
					bind:value={$settingsState.reward_order_statuses.value}
					items={Object.entries($settingsState.reward_order_statuses.options).map(
						([value, { label: name }]) => {
							return {
								value,
								name,
							};
						}
					)}
				/>

				<SettingsCheckboxes
					{...$settingsState.withdraw_order_statuses}
					bind:value={$settingsState.withdraw_order_statuses.value}
				/>
			</div>

			<div class="py-4">
				<SettingsSelect
					class="pb-4"
					{...$settingsState.giftcard_order_status}
					bind:value={$settingsState.giftcard_order_status.value}
					items={Object.entries($settingsState.giftcard_order_status.options).map(
						([value, { label: name }]) => {
							return {
								value,
								name,
							};
						}
					)}
				/>

				<SettingsCheckboxes
					{...$settingsState.giftcard_withdraw_order_statuses}
					bind:value={$settingsState.giftcard_withdraw_order_statuses.value}
				/>
			</div>
		{/if}
	</div>
</SettingsSection>
