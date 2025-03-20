<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import type { Tier } from "$lib/modules/leat/types";
	import { cn } from "$lib/utils/cn";

	export let tier: Tier;
	export let isCurrentTier: boolean;
</script>

<div class={cn("leat-dashboard-tier-card", isCurrentTier && "leat-dashboard-tier-card--current")}>
	{#if tier.media?.value}
		<div class="leat-dashboard-tier-card__icon">
			<img src={tier.media?.value} alt={__("Tier image", "leat-crm")} />
		</div>
	{/if}

	<h4 class="leat-dashboard-tier-card__header">
		{tier.name}
	</h4>

	{#if tier.description}
		<p class="leat-dashboard-tier-card__description">
			{tier.description}
		</p>
	{/if}
</div>

<style>
	.leat-dashboard-tier-card {
		border-radius: 0.375rem;
		position: relative;
		display: flex;
		flex-direction: column;
		justify-content: flex-start;
		align-items: center;
		background-color: var(--leat-dashboard-card-background-color, #fff);
		padding: 24px;
		text-align: center;
		box-shadow:
			0 0 #0000,
			0 0 #0000,
			0 1px 3px 0 rgb(0 0 0 / 0.1),
			0 1px 2px -1px rgb(0 0 0 / 0.1);
	}

	.leat-dashboard-tier-card--current {
		border: 2px solid var(--leat-dashboard-card-badge-background-color, #25a418);
	}

	.leat-dashboard-tier-card__icon {
		width: 100%;
		height: 80px;
		display: flex;
		justify-content: center;
		align-items: center;
		margin-bottom: 0.25rem;
	}

	.leat-dashboard-tier-card__icon img {
		max-width: 100%;
		max-height: 100%;
		object-fit: contain;
		border-radius: 0.375rem;
		box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
	}

	h4.leat-dashboard-tier-card__header {
		font-size: 1rem;
		margin: 0.5rem 0 0 0;
	}

	.leat-dashboard-tier-card__description {
		font-size: 0.675rem;
		margin: 0.1rem 0 0 0;
	}
</style>
