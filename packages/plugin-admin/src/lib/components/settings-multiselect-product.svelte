<script lang="ts">
	import type { PluginOptionsAdmin, PluginOptionsAdminKeys } from '@piggy/types/plugin';
	import { __ } from '@wordpress/i18n';
	import type { ObjectOption } from '$lib/components/multi-select';
	import MultiSelect from '$lib/components/multi-select';
	import { Label } from '$lib/components/ui/label';
	import { api } from '$lib/config/api';
	import { settingsState } from '$lib/stores/settings';
	import type { AdminProduct } from '$lib/types';
	import { onMount } from 'svelte';

	export let id: string;

	let items: ObjectOption[] = [];
	let selected: ObjectOption[] = [];
	let loading = false;
	let searchTerm = '';
	let option = $settingsState[id as PluginOptionsAdminKeys];

	if (!option) {
		console.warn(`No option found for id ${id}`);
	}

	const searchProducts = async (term: string) => {
		searchTerm = term;
		items = [];

		// At least 3 chars
		if (term.length < 3) {
			return;
		}

		const { data, error } = await api.get<AdminProduct[]>(`search_products`, {
			term
		});

		if (error) {
			return [];
		}

		if (data && data.length > 0) {
			items = data
				.map((item) => ({
					value: item.id.toString(),
					label: item.name
				}))
				.sort((a, b) => a.label.localeCompare(b.label));
		}
	};

	const onSearch = async (e: CustomEvent<{ query: string }>) => {
		loading = true;
		await searchProducts(e.detail.query);
		loading = false;
	};

	onMount(async () => {
		loading = true;

		if (
			!Array.isArray(option?.value) ??
			(Array.isArray(option?.value) && option?.value.length === 0)
		) {
			loading = false;
			return;
		}

		const { data } = await api.get<AdminProduct[]>('get_products', {
			ids: Array.isArray(option.value) ? option.value.join(',') : option.value
		});

		if (data && data.length > 0) {
			selected = data.map((item) => ({
				value: item.id.toString(),
				label: item.name
			}));

			items = selected;
		}

		loading = false;
	});

	// When `selected` changes we'll save just an array of ids [id, id, id].
	// This is because the API expects an array of ids for backwards compatibility.
	$: if (selected.length > 0) {
		const arr = [...selected.map((item) => item.value)] as string[];
		option.value = arr;
	}
</script>

{#if option}
	<Label for={id} class="mb-2">{option.label}</Label>

	<MultiSelect
		{id}
		form_input={null}
		bind:selected
		options={items}
		placeholder={__('Type to search for products...')}
		remote={true}
		noMatchingOptionsMsg={__('Start typing to see suggestions', 'piggy')}
		noResultsFoundMsg={searchTerm.length < 3
			? __('Type at least 3 characters to search', 'piggy')
			: __('No results found', 'piggy')}
		{loading}
		on:search={onSearch}
		outerDivClass="!max-w-xl"
		maxSelect={4}
	/>
{/if}
