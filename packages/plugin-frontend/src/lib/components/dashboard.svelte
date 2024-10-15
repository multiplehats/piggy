<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import DashboardCoupons from "./dashboard-coupons.svelte";
	import DashboardEarn from "./dashboard-earn.svelte";
	import DashboardHeaderPts from "./dashboard-header-pts.svelte";
	import DashboardRewards from "./dashboard-rewards.svelte";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { contactStore } from "$lib/stores";
	import { apiService } from "$lib/modules/piggy";

	const query = createQuery({
		queryKey: [QueryKeys.contact],
		queryFn: async () => await apiService.getContact(),
		enabled: window.piggyMiddlewareConfig.userId !== null,
	});

	$: contactStore.set($query.data ?? null);
</script>

<DashboardHeaderPts />
<DashboardCoupons />
<DashboardEarn />
<DashboardRewards />
