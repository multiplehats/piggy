<script lang="ts">
	import { createQuery } from '@tanstack/svelte-query';
	import { getShopsQueryConfig } from '$lib/modules/piggy/queries';
	import type { GetShopsResponse } from '$lib/modules/piggy/types';
	import SettingsCombobox from './settings-combobox.svelte';

	const options = [
		{
			label: 'Mediastap',
			value: 'mediastap'
		},
		{
			label: 'Chris Jayden',
			value: 'chris-jayden'
		},
		{
			label: 'Wonder & Melon',
			value: 'wonder-melon'
		}
	];

	const query = createQuery(getShopsQueryConfig());
</script>

{#if $query?.data}
	<SettingsCombobox
		items={$query.data.map((shop) => ({
			label: shop.name,
			value: shop.uuid
		}))}
		itemName="shop"
		id="shop"
		label="Shop"
	/>
{:else}
	Kiadubg
{/if}
