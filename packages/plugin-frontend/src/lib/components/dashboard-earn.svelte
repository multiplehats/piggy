<script lang="ts">
	import { replaceStrings } from "@leat/lib";
	import type { GetEarnRulesResponse } from "@leat/lib";
	import { getTranslatedText } from "@leat/i18n";
	import DashboardEarnCard from "./dashboard-earn-card.svelte";
	import { creditsName, pluginSettings } from "$lib/modules/settings";

	export let earnRules: GetEarnRulesResponse | null | undefined = undefined;

	function getNavItemText(text?: string) {
		if (!text) return "";

		return replaceStrings(text, [{ "{{credits_currency}}": $creditsName ?? "" }]);
	}
</script>

{#if earnRules}
	<div class="leat-dashboard-earn">
		<div>
			<h3 class="leat-dashboard__header">
				{getNavItemText(getTranslatedText($pluginSettings.dashboard_nav_earn))}
			</h3>

			{#if earnRules && earnRules.length > 0}
				<div class="leat-dashboard-earn__cards">
					{#each earnRules as earnRule}
						{#if earnRule.label.value}
							<DashboardEarnCard {earnRule} />
						{/if}
					{/each}
				</div>
			{/if}
		</div>
	</div>
{/if}

<style>
	.leat-dashboard-earn {
		text-align: center;
		max-width: 1260px;
		width: 100%;
		margin-top: 3rem;
	}

	.leat-dashboard-earn__cards {
		display: grid;
		background: var(--leat-dashboard-card-background-color, #f7f7f7);
		padding: 1.4rem;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.leat-dashboard-earn__cards {
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		}
	}

	@media (max-width: 480px) {
		.leat-dashboard-earn__cards {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
