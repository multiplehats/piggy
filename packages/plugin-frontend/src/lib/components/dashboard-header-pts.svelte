<script lang="ts">
	import { currentLanguage, isLoggedIn, pluginSettings } from '$lib/modules/settings';
	import { replacePlaceholders } from '$lib/utils/replace-text-vars';
	import BadgeEuro from 'lucide-svelte/icons/badge-euro';
	import BarChart from 'lucide-svelte/icons/bar-chart';
	import ShoppingBag from 'lucide-svelte/icons/shopping-bag';
	import Tag from 'lucide-svelte/icons/tag';

	const navItems = [
		{
			icon: Tag,
			text: $pluginSettings?.dashboard_nav_coupons?.[currentLanguage]
		},
		{
			icon: BadgeEuro,
			text: $pluginSettings?.dashboard_nav_earn?.[currentLanguage]
		},
		{
			icon: ShoppingBag,
			text: $pluginSettings?.dashboard_nav_rewards?.[currentLanguage]
		},
		{
			icon: BarChart,
			text: $pluginSettings?.dashboard_nav_activity?.[currentLanguage]
		}
	];
</script>

<section>
	<div class="piggy-dashboard__container">
		<h2 class="piggy-dashboard__header">
			{#if isLoggedIn}
				{replacePlaceholders($pluginSettings?.dashboard_title_logged_in?.[currentLanguage])}
			{:else}
				{replacePlaceholders($pluginSettings?.dashboard_title_logged_out?.[currentLanguage])}
			{/if}
		</h2>

		<!-- Call to action-->
		{#if !isLoggedIn}
			CTA
		{/if}

		{#if isLoggedIn}
			<nav class="piggy-dashboard__nav">
				<ul class="piggy-dashboard__list">
					{#each navItems as { icon, text }, i}
						<li>
							<button class="piggy-dashboard__item">
								<svelte:component this={icon} size={24} />

								{replacePlaceholders(text)}
							</button>
						</li>
					{/each}
				</ul>
			</nav>
		{/if}
	</div>
</section>

<style lang="postcss">
	.piggy-dashboard__container {
		text-align: center;
	}

	.piggy-dashboard__header {
		font-size: 1.5rem;
		margin: 0;
		margin-bottom: 2rem;
	}

	.piggy-dashboard__nav {
		justify-content: center;
		display: flex;
		align-items: center;
	}

	.piggy-dashboard__list {
		display: inline-flex;
		border-bottom: 1px solid var(--piggy-dashboard-nav-item-border, #e5e5e5);
		gap: 6px;
		justify-content: center;
		flex-wrap: wrap;
		list-style-type: none;
		margin: 0;
	}

	.piggy-dashboard__item {
		outline: none;
		display: inline-flex;
		cursor: pointer;
		align-items: center;
		gap: 8px;
		padding: 8px 12px;
		letter-spacing: 0.025em;
		border-radius: 0;
		border: none;
		background-color: transparent;
	}

	.piggy-dashboard__item:hover {
		background-color: var(--piggy-dashboard-nav-item-bg-hover, #f5f5f5);
	}

	@media (max-width: 768px) {
		.piggy-dashboard__list {
			border-bottom: none;
			width: 100%;
			display: grid;
			grid-template-columns: repeat(1, 1fr);
		}

		.piggy-dashboard__item {
			border-radius: 6px;
			background-color: var(--piggy-dashboard-nav-item-bg, #f5f5f5);
			width: 100%;
		}
	}
</style>
