<script lang="ts">
	import { createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { PiggyAdminService } from '$lib/modules/piggy';
	import type { GetSpendRuleByIdResponse } from '$lib/modules/settings/types';
	import { QueryKeys } from '$lib/utils/query-keys';
	import SettingsCombobox from '../settings-combobox.svelte';
	import { Alert } from '../ui/alert';

	export let selectedReward: GetSpendRuleByIdResponse[0]['selectedReward'];

	const service = new PiggyAdminService();
	const client = useQueryClient();
	const query = createQuery({
		queryKey: [QueryKeys.piggyRewards],
		queryFn: async () => await service.getRewards()
	});
</script>

{#if $query.isLoading}
	<p>Loading...</p>
{:else if $query.isError}
	<Alert
		description={__(`Error retrieving rewards: ${$query.error.message}`, 'piggy')}
		type="error"
	/>
{:else if $query.isSuccess}
	{#if $query.data}
		<SettingsCombobox
			items={$query?.data
				? $query.data.map((reward) => ({
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
{/if}
