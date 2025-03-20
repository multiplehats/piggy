<script lang="ts">
	import DashboardTierCard from "./dashboard-tier-card.svelte";
	import { pluginSettings } from "$lib/modules/settings";
	import { getTranslatedText } from "$lib/utils/translated-text";
	import type { Tier } from "$lib/modules/leat/types";

	export let tiers: Tier[] = [];
	export let currentTier: Tier | null | undefined = undefined;

	$: tiersPositioned = tiers?.sort((a, b) => a.position - b.position);
</script>

<div class="leat-dashboard-tiers">
	<div>
		<h3 class="leat-dashboard__header">
			{getTranslatedText($pluginSettings?.dashboard_nav_tiers)}
		</h3>
	</div>

	{#if tiersPositioned && tiersPositioned.length > 0}
		<div class="leat-dashboard-tiers__cards">
			{#each tiersPositioned as tier}
				<DashboardTierCard {tier} isCurrentTier={tier.id === currentTier?.id} />
			{/each}
		</div>
	{/if}
</div>

<style>
	.leat-dashboard-tiers {
		text-align: center;
		max-width: 1260px;
		width: 100%;
		margin-top: 3rem;
	}

	.leat-dashboard-tiers__cards {
		display: grid;
		background: var(--leat-dashboard-card-background-color, #f7f7f7);
		padding: 1.4rem;
		grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
		gap: 1rem;
	}

	@media (max-width: 768px) {
		.leat-dashboard-tiers__cards {
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		}
	}

	@media (max-width: 480px) {
		.leat-dashboard-tiers__cards {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
