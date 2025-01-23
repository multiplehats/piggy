<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import BadgeEuro from "lucide-svelte/icons/badge-euro";
	import ShoppingBag from "lucide-svelte/icons/shopping-bag";
	import Tag from "lucide-svelte/icons/tag";
	import DashboardCoupons from "./dashboard-coupons.svelte";
	import DashboardEarn from "./dashboard-earn.svelte";
	import DashboardHeaderPts from "./dashboard-header-pts.svelte";
	import DashboardRewards from "./dashboard-rewards.svelte";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { contactStore } from "$lib/stores";
	import { apiService } from "$lib/modules/leat";
	import { isLoggedIn, pluginSettings } from "$lib/modules/settings";
	import { getTranslatedText } from "$lib/utils/translated-text";

	// Contact query
	const contactQuery = createQuery({
		queryKey: [QueryKeys.contact],
		queryFn: async () => await apiService.getContact(window.leatMiddlewareConfig.userId),
		enabled: window.leatMiddlewareConfig.userId !== null,
	});

	// Coupons query
	const couponsQuery = createQuery({
		queryKey: [QueryKeys.coupons],
		queryFn: async () => await apiService.getCoupons(window.leatMiddlewareConfig.userId),
		enabled: isLoggedIn,
	});

	// Earn rules query
	const earnRulesQuery = createQuery({
		queryKey: [QueryKeys.earnRules],
		queryFn: async () => await apiService.getEarnRules(),
	});

	// Spend rules query
	const spendRulesQuery = createQuery({
		queryKey: [QueryKeys.spendRules],
		queryFn: async () => await apiService.getSpendRules(window.leatMiddlewareConfig.userId),
		enabled: isLoggedIn,
	});

	$: contactStore.set($contactQuery.data ?? null);

	// Compute visibility of sections
	$: filteredSpendRules =
		$spendRulesQuery.data?.filter(
			(rule) => rule.status.value === "publish" && rule.label.value
		) ?? [];

	$: navItems = [
		{
			icon: Tag,
			id: "coupons",
			text: getTranslatedText($pluginSettings?.dashboard_nav_coupons),
			show: true,
		},
		{
			icon: BadgeEuro,
			id: "earn",
			text: getTranslatedText($pluginSettings?.dashboard_nav_earn),
			show: true,
		},
		{
			icon: ShoppingBag,
			id: "rewards",
			text: getTranslatedText($pluginSettings?.dashboard_nav_rewards),
			show: filteredSpendRules.length > 0,
		},
		// {
		// 	icon: BarChart,
		// 	id: 'activity',
		// 	text: getTranslatedText($pluginSettings?.dashboard_nav_activity)
		// }
	];
</script>

<DashboardHeaderPts {navItems} />

<DashboardCoupons
	coupons={$couponsQuery.data}
	isLoading={$couponsQuery.isLoading}
	isSuccess={$couponsQuery.isSuccess}
/>

<DashboardEarn earnRules={$earnRulesQuery.data ?? []} />

<DashboardRewards spendRules={filteredSpendRules} />
