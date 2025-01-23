<script lang="ts">
	import { replaceStrings } from "@leat/lib";
	import DashboardSpendRuleCard from "./dashboard-spend-rule-card.svelte";
	import { creditsName, pluginSettings } from "$lib/modules/settings";
	import { getTranslatedText } from "$lib/utils/translated-text";
	import type { SpendRule } from "$lib/modules/leat/types";

	export let spendRules: SpendRule[] | null | undefined = undefined;

	function getNavItemText(text?: string) {
		if (!text) return "";

		return replaceStrings(text, [{ "{{credits_currency}}": $creditsName ?? "" }]);
	}
</script>

{#if spendRules && spendRules.length > 0}
	<div class="leat-dashboard-rewards">
		<div>
			<h3 class="leat-dashboard__header">
				{getNavItemText(getTranslatedText($pluginSettings.dashboard_nav_rewards))}
			</h3>

			<div class="leat-dashboard-rewards__cards">
				{#each spendRules as rule}
					<DashboardSpendRuleCard {rule} />
				{/each}
			</div>
		</div>
	</div>
{/if}

<style>
	.leat-dashboard-rewards {
		text-align: center;
		max-width: 1260px;
		width: 100%;
		margin-top: 3rem;
	}

	.leat-dashboard-rewards__cards {
		display: grid;
		background: var(--leat-dashboard-card-background-color, #f7f7f7);
		padding: 1.4rem;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.leat-dashboard-rewards__cards {
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		}
	}

	@media (max-width: 480px) {
		.leat-dashboard-rewards__cards {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
