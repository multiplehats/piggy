<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { PiggyAdminService } from '$lib/modules/piggy';
	import { QueryKeys } from '$lib/utils/query-keys';
	import SettingsCombobox from '../settings-combobox.svelte';

	const service = new PiggyAdminService();

	// TODO: Fetch products
	const query = createQuery({
		queryKey: [QueryKeys.wcProducts],
		retry: false,
		queryFn: async () => service.searchProducts('t-shirt'),
		refetchOnWindowFocus: true
	});

	$: console.log($query.data);
</script>

<div class="grid">
	<!-- <SettingsCombobox
		items={$query?.data
			? $query.data.map((reward) => ({
					label: product.title,
					value: product.id
			  }))
			: []}
		itemName={__('Products', 'piggy')}
	/> -->
</div>
