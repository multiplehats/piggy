<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import SettingsSelect from "./settings-select.svelte";
	import SettingsCheckboxes from "./settings-checkboxes.svelte";
	import SettingsSwitch from "./settings-switch.svelte";

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
			<div>
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
					class="pb-4"
					{...$settingsState.giftcard_withdraw_order_statuses}
					bind:value={$settingsState.giftcard_withdraw_order_statuses.value}
				/>
			</div>

			<SettingsSwitch
				class="py-4"
				{...$settingsState.giftcard_disable_recipient_email}
				bind:value={$settingsState.giftcard_disable_recipient_email.value}
			/>
		{/if}
	</div>
</SettingsSection>
