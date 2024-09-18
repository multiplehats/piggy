<script lang="ts">
	import { createQuery } from '@tanstack/svelte-query';
	import { apiService } from '$lib/modules/piggy';
	import { contactStore } from '$lib/stores';
	import { QueryKeys } from '$lib/utils/query-keys';
	import DashboardCoupons from './dashboard-coupons.svelte';
	import DashboardEarn from './dashboard-earn.svelte';
	import DashboardHeaderPts from './dashboard-header-pts.svelte';
	import DashboardRewards from './dashboard-rewards.svelte';

	const query = createQuery({
		queryKey: [QueryKeys.contact],
		retry: false,
		queryFn: async () => await apiService.getContact()
	});

	$: contactStore.set($query.data ?? null);
	$: console.log($query.data);
</script>

<DashboardHeaderPts />
<DashboardCoupons />
<DashboardEarn />
<DashboardRewards />
