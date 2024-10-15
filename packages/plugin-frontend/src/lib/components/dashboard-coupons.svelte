<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import { replaceStrings } from "@piggy/lib";
	import DashboardCouponCard from "./dashboard-coupon-card.svelte";
	import { apiService } from "$lib/modules/piggy";
	import { currentLanguage, isLoggedIn, pluginSettings } from "$lib/modules/settings";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { getTranslatedText } from "$lib/utils/translated-text";

	const query = createQuery({
		queryKey: [QueryKeys.coupons],
		queryFn: async () => await apiService.getCoupons(window.piggyMiddlewareConfig.userId),
		enabled: isLoggedIn,
	});

	function getNavItemText(text?: string) {
		if (!text) return "";

		const creditsName = $pluginSettings?.credits_name?.[currentLanguage];

		return replaceStrings(text, [{ "{{credits_currency}}": creditsName ?? "" }]);
	}
</script>

{#if isLoggedIn}
	<div class="piggy-dashboard-coupons">
		<div>
			<h3 class="piggy-dashboard__header">
				{getNavItemText(getTranslatedText($pluginSettings?.dashboard_nav_coupons))}
			</h3>
		</div>

		{#if $query.isLoading}
			<div class="piggy-dashboard-coupons__loading">
				<p>{getTranslatedText($pluginSettings?.dashboard_coupons_loading_state)}</p>
			</div>
		{/if}

		{#if $query.isSuccess && $query.data}
			{@const filteredCoupons = $query.data.filter(
				(coupon) =>
					coupon.spend_rule.status.value === "publish" && coupon.spend_rule.label.value
			)}

			{#if filteredCoupons.length > 0}
				<div class="piggy-dashboard-coupons__cards">
					{#each filteredCoupons as coupon}
						<DashboardCouponCard {coupon} />
					{/each}
				</div>
			{:else}
				<div class="piggy-dashboard-coupons__empty">
					<p>
						{getTranslatedText($pluginSettings?.dashboard_nav_coupons_empty_state)}
					</p>
				</div>
			{/if}
		{/if}
	</div>
{/if}

<style>
	.piggy-dashboard-coupons {
		text-align: center;
		max-width: 1260px;
		width: 100%;
		margin-top: 3rem;
	}

	.piggy-dashboard__header {
		font-size: 1.5rem;
		margin: 0;
		margin-bottom: 1.5rem;
		margin-left: auto;
		margin-right: auto;
		max-width: 450px;
	}

	.piggy-dashboard-coupons__cards {
		display: grid;
		background: var(--piggy-dashboard-card-background-color, #f7f7f7);
		padding: 1.4rem;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.piggy-dashboard-coupons__cards {
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		}
	}

	@media (max-width: 480px) {
		.piggy-dashboard-coupons__cards {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
