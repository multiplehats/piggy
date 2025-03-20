<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import BadgeEuro from "lucide-svelte/icons/badge-euro";
	import ShoppingBag from "lucide-svelte/icons/shopping-bag";
	import Layers from "lucide-svelte/icons/layers";
	import Tag from "lucide-svelte/icons/tag";
	import DashboardCoupons from "./dashboard-coupons.svelte";
	import DashboardEarn from "./dashboard-earn.svelte";
	import DashboardHeaderPts from "./dashboard-header-pts.svelte";
	import DashboardRewards from "./dashboard-rewards.svelte";
	import DashboardTiers from "./dashboard-tiers.svelte";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { contactStore } from "$lib/stores";
	import { apiService } from "$lib/modules/leat";
	import { isLoggedIn, pluginSettings } from "$lib/modules/settings";
	import { getTranslatedText } from "$lib/utils/translated-text";

	const contactQuery = createQuery({
		queryKey: [QueryKeys.contact],
		queryFn: async () => await apiService.getContact(window.leatMiddlewareConfig.userId!),
		enabled: window.leatMiddlewareConfig.userId !== null,
	});

	const couponsQuery = createQuery({
		queryKey: [QueryKeys.coupons],
		queryFn: async () => await apiService.getCoupons(window.leatMiddlewareConfig.userId),
		enabled: isLoggedIn,
	});

	const earnRulesQuery = createQuery({
		queryKey: [QueryKeys.earnRules],
		queryFn: async () => await apiService.getEarnRules(),
	});

	const spendRulesQuery = createQuery({
		queryKey: [QueryKeys.spendRules],
		queryFn: async () => await apiService.getSpendRules(window.leatMiddlewareConfig.userId),
		enabled: isLoggedIn,
	});

	const tiersQuery = createQuery({
		queryKey: [QueryKeys.tiers],
		queryFn: async () => await apiService.getTiers(),
	});

	$: contactStore.set($contactQuery.data ?? null);

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
			show: !!filteredSpendRules.length,
		},
		{
			icon: Layers,
			id: "tiers",
			text: getTranslatedText($pluginSettings?.dashboard_nav_tiers),
			show: !!(
				$tiersQuery.data?.tiers &&
				$tiersQuery.data?.tiers.length > 0 &&
				$pluginSettings?.dashboard_show_tiers === "on"
			),
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

{#if $tiersQuery.data?.tiers && $tiersQuery.data?.tiers.length > 0 && $pluginSettings?.dashboard_show_tiers === "on"}
	<DashboardTiers tiers={$tiersQuery.data?.tiers} currentTier={$contactQuery.data?.tier} />
{/if}
