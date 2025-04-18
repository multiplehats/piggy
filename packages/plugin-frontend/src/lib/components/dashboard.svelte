<script lang="ts">
	import { createQueries } from "@tanstack/svelte-query";
	import BadgeEuro from "lucide-svelte/icons/badge-euro";
	import ShoppingBag from "lucide-svelte/icons/shopping-bag";
	import Layers from "lucide-svelte/icons/layers";
	import Tag from "lucide-svelte/icons/tag";
	import { getContact, getCoupons, getEarnRules, getSpendRules, getTiers } from "@leat/lib";
	import { getTranslatedText } from "@leat/i18n";
	import DashboardCoupons from "./dashboard-coupons.svelte";
	import DashboardEarn from "./dashboard-earn.svelte";
	import DashboardHeaderPts from "./dashboard-header-pts.svelte";
	import DashboardRewards from "./dashboard-rewards.svelte";
	import DashboardTiers from "./dashboard-tiers.svelte";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { contactStore } from "$lib/stores";
	import { isLoggedIn, pluginSettings } from "$lib/modules/settings";

	const queries = createQueries({
		queries: [
			{
				queryKey: [QueryKeys.contact],
				queryFn: async () => await getContact(window.leatMiddlewareConfig.userId!),
				enabled: window.leatMiddlewareConfig.userId !== null,
			},
			{
				queryKey: [QueryKeys.coupons],
				queryFn: async () => await getCoupons(window.leatMiddlewareConfig.userId),
				enabled: isLoggedIn,
			},
			{
				queryKey: [QueryKeys.earnRules],
				queryFn: async () => await getEarnRules(),
			},
			{
				queryKey: [QueryKeys.spendRules],
				queryFn: async () => await getSpendRules(window.leatMiddlewareConfig.userId),
				enabled: isLoggedIn,
			},
			{
				queryKey: [QueryKeys.tiers],
				queryFn: async () => await getTiers(),
			},
		],
	});

	$: contactStore.set($queries[0].data ?? null);

	$: filteredSpendRules =
		$queries[3].data?.filter((rule) => rule.status.value === "publish" && rule.label.value) ??
		[];

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
				$queries[4].data?.tiers &&
				$queries[4].data?.tiers.length > 0 &&
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
	coupons={$queries[1].data}
	isLoading={$queries[1].isLoading}
	isSuccess={$queries[1].isSuccess}
/>

<DashboardEarn earnRules={$queries[2].data ?? []} />

<DashboardRewards spendRules={filteredSpendRules} />

{#if $queries[4].data?.tiers && $queries[4].data?.tiers.length > 0 && $pluginSettings?.dashboard_show_tiers === "on"}
	<DashboardTiers tiers={$queries[4].data?.tiers} currentTier={$queries[0].data?.tier} />
{/if}
