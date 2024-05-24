<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import SettingsInput from '$lib/components/settings-input.svelte';
	import { setApiKeyMutationConfig } from '$lib/modules/piggy/mutations';
	import { getApiKeyQueryConfig, getShopsQueryConfig } from '$lib/modules/piggy/queries';
	import type { AdminGetApiKeyResponse } from '$lib/modules/piggy/types';
	import { settingsState } from '$lib/stores/settings';
	import SettingsCombobox from '../settings-combobox.svelte';
	import SettingsSection from '../ui/settings-section/settings-section.svelte';

	export let isLoading = false;

	const client = useQueryClient();
	const setApiKeyMutation = createMutation(setApiKeyMutationConfig(client));
	const query = createQuery<AdminGetApiKeyResponse>(getApiKeyQueryConfig());
	const shopQuery = createQuery(getShopsQueryConfig());

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
			on:change={({ currentTarget }) => $setApiKeyMutation.mutate({ api_key: currentTarget.value })}
		/>

		{#if $query.data?.api_key}
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
