<script lang="ts">
	import { createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { PiggyAdminService } from '$lib/modules/piggy';
	import type { GetSpendRuleByIdResponse } from '$lib/modules/settings/types';
	import { QueryKeys } from '$lib/utils/query-keys';
	import SettingsCombobox from '../settings-combobox.svelte';

	export let selectedReward: GetSpendRuleByIdResponse[0]['selectedReward'];

	const service = new PiggyAdminService();
	const client = useQueryClient();
	const query = createQuery({
		queryKey: [QueryKeys.piggyRewards],
		queryFn: async () => await service.getRewards()
	});

	// filter on attributes.pre_redeemable === true
	$: filteredRewards = $query.data?.filter((reward) => reward.attributes.pre_redeemable === true);
</script>

{#if filteredRewards}
	<SettingsCombobox
		items={filteredRewards
			? filteredRewards.map((reward) => ({
					label: reward.title,
					value: reward.uuid
			  }))
			: []}
		itemName={__('Reward', 'piggy')}
		label={selectedReward.label}
		description={selectedReward.description}
		id={selectedReward.id}
		bind:value={selectedReward.value}
	/>
{/if}
