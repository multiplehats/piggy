<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import { replaceStrings } from "@piggy/lib";
	import DashboardSpendRuleCard from "./dashboard-spend-rule-card.svelte";
	import { apiService } from "$lib/modules/piggy";
	import { creditsName, isLoggedIn, pluginSettings } from "$lib/modules/settings";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { getTranslatedText } from "$lib/utils/translated-text";

	const query = createQuery({
		queryKey: [QueryKeys.spendRules],
		queryFn: async () => await apiService.getSpendRules(window.piggyMiddlewareConfig.userId),
		enabled: isLoggedIn,
	});

	function getNavItemText(text?: string) {
		if (!text) return "";

		return replaceStrings(text, [{ "{{credits_currency}}": $creditsName ?? "" }]);
	}

	$: filteredRules = $query.data?.filter(
		(rule) => rule.status.value === "publish" && rule.label.value
	);
</script>

{#if filteredRules && filteredRules.length > 0}
	<div class="piggy-dashboard-rewards">
		<div>
			<h3 class="piggy-dashboard__header">
				{getNavItemText(getTranslatedText($pluginSettings.dashboard_nav_rewards))}
			</h3>

			<div class="piggy-dashboard-rewards__cards">
				{#if filteredRules && filteredRules.length > 0}
					{#each filteredRules as rule}
						<DashboardSpendRuleCard {rule} />
					{/each}
				{/if}
			</div>
		</div>
	</div>
{/if}

<style>
	.piggy-dashboard-rewards {
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

	.piggy-dashboard-rewards__cards {
		display: grid;
		background: var(--piggy-dashboard-card-background-color, #f7f7f7);
		padding: 1.4rem;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.piggy-dashboard-rewards__cards {
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		}
	}

	@media (max-width: 480px) {
		.piggy-dashboard-rewards__cards {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
