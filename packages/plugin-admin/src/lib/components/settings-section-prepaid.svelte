<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import SettingsSelect from "./settings-select.svelte";
	import SettingsCheckboxes from "./settings-checkboxes.svelte";

	import { Alert } from "./ui/alert";
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

{#if $query.isLoading}
	<p>{__("Loading settings")}</p>
{:else if $query.isError}
	<p>Error: {$query.error.message}</p>
{:else if $query.isSuccess && $settingsState}
	<div class="grid grid-cols-2 gap-4">
		<SettingsSection title={__("Selling Prepaid")}>
			<div class="divide-border w-full divide-y">
				<div>
					<SettingsSelect
						class="pb-4"
						{...$settingsState.prepaid_order_status}
						bind:value={$settingsState.prepaid_order_status.value}
						items={Object.entries($settingsState.prepaid_order_status.options).map(
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
						{...$settingsState.prepaid_withdraw_order_statuses}
						bind:value={$settingsState.prepaid_withdraw_order_statuses.value}
					/>
				</div>
			</div>
		</SettingsSection>

		<SettingsSection title={__("Redeeming Prepaid")}>
			<div class="divide-border w-full divide-y">
				<Alert type="info">
					<p>
						{__("Redeeming prepaid is not yet available.", "leat-crm")}
					</p>
				</Alert>
			</div>
		</SettingsSection>
	</div>
{/if}
