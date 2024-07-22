<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import SettingsInput from '$lib/components/settings-input.svelte';
	import { getShopsQueryConfig } from '$lib/modules/piggy/queries';
	import { saveSettingsMutationConfig } from '$lib/modules/settings/mutations';
	import { getSettingByIdQueryConfig } from '$lib/modules/settings/queries';
	import { settingsState } from '$lib/stores/settings';
	import { QueryKeys } from '$lib/utils/query-keys';
	import SettingsCombobox from '../settings-combobox.svelte';
	import SettingsSection from '../ui/settings-section/settings-section.svelte';

	export let isLoading = false;

	const client = useQueryClient();
	const query = createQuery(getSettingByIdQueryConfig('api_key'));
	const shopQuery = createQuery(getShopsQueryConfig());
	const saveSettingsMutation = createMutation(
		saveSettingsMutationConfig(client, {
			onSuccess: async () => {
				await client.invalidateQueries({ queryKey: [QueryKeys.piggyShops] });
			}
		})
	);

	$: {
		isLoading = $query.isLoading || $shopQuery.isLoading;
	}
</script>

<SettingsSection title={__('Connect to Piggy', 'piggy')}>
	<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
		<SettingsInput
			label={$settingsState.api_key.label}
			description={$settingsState.api_key.description}
			id={$settingsState.api_key.id}
			bind:value={$settingsState.api_key.value}
			class="font-mono"
			on:change={({ currentTarget }) => $saveSettingsMutation.mutate(settingsState)}
		/>

		{#if $query.data?.value}
			<SettingsCombobox
				items={$shopQuery?.data
					? $shopQuery.data.map((shop) => ({
							label: shop.name,
							value: shop.uuid
					  }))
					: []}
				itemName="shop"
				label={$settingsState.shop_uuid.label}
				description={$settingsState.shop_uuid.description}
				id={$settingsState.shop_uuid.id}
				bind:value={$settingsState.shop_uuid.value}
			/>
		{/if}
	</div>
</SettingsSection>
