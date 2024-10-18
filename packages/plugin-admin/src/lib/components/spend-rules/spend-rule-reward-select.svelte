<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import SettingsCombobox from "../settings-combobox.svelte";
	import { Alert } from "../ui/alert";
	import { LeatAdminService } from "$lib/modules/leat";
	import type { GetSpendRuleByIdResponse } from "$lib/modules/settings/types";
	import { QueryKeys } from "$lib/utils/query-keys";

	export let selectedReward: GetSpendRuleByIdResponse[0]["selectedReward"];

	const service = new LeatAdminService();

	const query = createQuery({
		queryKey: [QueryKeys.leatRewards],
		queryFn: async () => await service.getRewards(),
	});

	// filter on attributes.pre_redeemable === true
	$: filteredRewards = $query.data?.filter((reward) => reward.attributes.pre_redeemable === true);
</script>

{#if $query.isLoading}
	<p>Loading...</p>
{:else if $query.isError}
	<Alert
		description={__(`Error retrieving rewards: ${$query.error.message}`, "leat")}
		type="error"
	/>
{:else if $query.isSuccess}
	{#if filteredRewards}
		<SettingsCombobox
			items={filteredRewards
				? filteredRewards.map((reward) => ({
						label: reward.title,
						value: reward.uuid,
					}))
				: []}
			itemName={__("Reward", "leat")}
			label={selectedReward.label}
			description={selectedReward.description}
			id={selectedReward.id}
			bind:value={selectedReward.value}
		/>
	{/if}
{/if}
