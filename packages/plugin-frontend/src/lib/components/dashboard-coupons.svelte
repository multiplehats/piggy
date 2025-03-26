<script lang="ts">
	import { replaceStrings } from "@leat/lib";
	import type { GetCouponsResponse } from "@leat/lib";
	import { getTranslatedText } from "@leat/i18n";
	import DashboardCouponCard from "./dashboard-coupon-card.svelte";
	import { currentLanguage, isLoggedIn, pluginSettings } from "$lib/modules/settings";

	export let coupons: GetCouponsResponse | undefined | null = undefined;
	export let isLoading: boolean;
	export let isSuccess: boolean;

	function getNavItemText(text?: string) {
		if (!text) return "";

		const creditsName = $pluginSettings?.credits_name?.[currentLanguage];

		return replaceStrings(text, [{ "{{credits_currency}}": creditsName ?? "" }]);
	}
</script>

{#if isLoggedIn}
	<div class="leat-dashboard-coupons">
		<div>
			<h3 class="leat-dashboard__header">
				{getNavItemText(getTranslatedText($pluginSettings?.dashboard_nav_coupons))}
			</h3>
		</div>

		{#if isLoading}
			<div class="leat-dashboard-coupons__loading">
				<p>{getTranslatedText($pluginSettings?.dashboard_coupons_loading_state)}</p>
			</div>
		{/if}

		{#if isSuccess && coupons}
			{@const filteredSpendRuleCoupons = coupons.spend_rules_coupons.filter(
				(c) => c.rule.status.value === "publish" && c.rule.label.value
			)}
			{@const filteredPromotionRuleCoupons = coupons.promotion_rules_coupons.filter(
				(c) => c.rule.status.value === "publish" && c.rule.label.value
			)}
			{@const allFilteredCoupons = [
				...filteredSpendRuleCoupons,
				...filteredPromotionRuleCoupons,
			]}

			{#if allFilteredCoupons.length > 0}
				<div class="leat-dashboard-coupons__cards">
					{#each allFilteredCoupons as coupon}
						<DashboardCouponCard {coupon} />
					{/each}
				</div>
			{:else}
				<div class="leat-dashboard-coupons__empty">
					<p>
						{getTranslatedText($pluginSettings?.dashboard_nav_coupons_empty_state)}
					</p>
				</div>
			{/if}
		{/if}
	</div>
{/if}

<style>
	.leat-dashboard-coupons {
		text-align: center;
		max-width: 1260px;
		width: 100%;
		margin-top: 3rem;
	}

	.leat-dashboard-coupons__cards {
		display: grid;
		background: var(--leat-dashboard-card-background-color, #f7f7f7);
		padding: 1.4rem;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.leat-dashboard-coupons__cards {
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		}
	}

	@media (max-width: 480px) {
		.leat-dashboard-coupons__cards {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
