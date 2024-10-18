<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import { replaceStrings } from "@leat/lib";
	import DashboardEarnCard from "./dashboard-earn-card.svelte";
	import { apiService } from "$lib/modules/leat";
	import { creditsName, pluginSettings } from "$lib/modules/settings";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { getTranslatedText } from "$lib/utils/translated-text";

	const query = createQuery({
		queryKey: [QueryKeys.earnRules],
		queryFn: async () => await apiService.getEarnRules(),
	});

	function getNavItemText(text?: string) {
		if (!text) return "";

		return replaceStrings(text, [{ "{{credits_currency}}": $creditsName ?? "" }]);
	}
</script>

<div class="leat-dashboard-earn">
	<div>
		<h3 class="leat-dashboard__header">
			{getNavItemText(getTranslatedText($pluginSettings.dashboard_nav_earn))}
		</h3>

		{#if $query.data && $query.data.length > 0}
			<div class="leat-dashboard-earn__cards">
				{#each $query.data as earnRule}
					{#if earnRule.label.value}
						<DashboardEarnCard {earnRule} />
					{/if}
				{/each}
			</div>
		{/if}
	</div>
</div>

<style>
	.leat-dashboard-earn {
		text-align: center;
		max-width: 1260px;
		width: 100%;
		margin-top: 3rem;
	}

	.leat-dashboard__header {
		font-size: 1.5rem;
		margin: 0;
		margin-bottom: 1.5rem;
		margin-left: auto;
		margin-right: auto;
		max-width: 450px;
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
