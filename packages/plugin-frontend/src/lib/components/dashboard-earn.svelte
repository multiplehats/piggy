<script lang="ts">
	import { creditsName, pluginSettings } from '$lib/modules/settings';
	import { getTranslatedText } from '$lib/utils/translated-text';
	import { replaceStrings } from '@piggy/lib';
	import DashboardEarnCard from './dashboard-earn-card.svelte';

	const earnRules = window.piggyEarnRules;

	function getNavItemText(text?: string) {
		if (!text) return '';

		return replaceStrings(text, [{ '{{credits_currency}}': $creditsName ?? '' }]);
	}
</script>

<div class="piggy-dashboard-earn">
	<div>
		<h3 class="piggy-dashboard__header">
			{getNavItemText(getTranslatedText($pluginSettings.dashboard_nav_earn))}
		</h3>

		{#if earnRules}
			<div class="piggy-dashboard-earn__cards">
				{#each earnRules as earnRule}
					{#if earnRule.label.value}
						<DashboardEarnCard {earnRule} />
					{/if}
				{/each}
			</div>
		{/if}
	</div>
</div>

<style>
	.piggy-dashboard-earn {
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

	.piggy-dashboard-earn__cards {
		display: grid;
		background: var(--piggy-dashboard-card-background-color, #f7f7f7);
		padding: 1.4rem;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.piggy-dashboard-earn__cards {
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		}
	}

	@media (max-width: 480px) {
		.piggy-dashboard-earn__cards {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
