<script lang="ts">
	import { createMutation } from '@tanstack/svelte-query';
	import { Button } from '$lib/components/button/index.js';
	import { piggyService } from '$lib/config/services';
	import { isLoggedIn, pluginSettings } from '$lib/modules/settings';
	import { contactStore, hasPiggyAccount } from '$lib/stores';
	import { MutationKeys } from '$lib/utils/query-keys';
	import { getTranslatedText } from '$lib/utils/translated-text';
	import BadgeEuro from 'lucide-svelte/icons/badge-euro';
	import BarChart from 'lucide-svelte/icons/bar-chart';
	import ShoppingBag from 'lucide-svelte/icons/shopping-bag';
	import Tag from 'lucide-svelte/icons/tag';
	import { replaceStrings } from '@piggy/lib';

	const joinProgramMutation = createMutation({
		mutationKey: [MutationKeys.joinProgram],
		mutationFn: () => piggyService.joinProgram(window.piggyMiddlewareConfig.userId),
		onSuccess: () => {
			location.reload();
		}
	});

	const navItems = [
		{
			icon: Tag,
			id: 'coupons',
			text: getTranslatedText($pluginSettings?.dashboard_nav_coupons)
		},
		{
			icon: BadgeEuro,
			id: 'earn',
			text: getTranslatedText($pluginSettings?.dashboard_nav_earn)
		},
		{
			icon: ShoppingBag,
			id: 'rewards',
			text: getTranslatedText($pluginSettings?.dashboard_nav_rewards)
		}
		// {
		// 	icon: BarChart,
		// 	id: 'activity',
		// 	text: getTranslatedText($pluginSettings?.dashboard_nav_activity)
		// }
	];

	function getHeaderTitle(text: string, credits: number | string) {
		if (!text) return '';

		const creditsName = getTranslatedText($pluginSettings?.credits_name);

		return replaceStrings(text, [
			{
				'{{credits_currency}}': creditsName ?? '',
				'{{credits}}': credits?.toString() ?? '0'
			}
		]);
	}

	function getNavItemText(text?: string) {
		if (!text) return '';

		const creditsName = getTranslatedText($pluginSettings?.credits_name);

		return replaceStrings(text, [{ '{{credits_currency}}': creditsName ?? '' }]);
	}

	function handleScrollNavigation(id: string) {
		// scroll to the nearest `piggy-dashboard-${id}` element
		return () => {
			const element = document.querySelector(`.piggy-dashboard-${id}`);

			if (element) {
				element.scrollIntoView({ behavior: 'smooth' });
			}
		};
	}

	function handleJoinProgram() {
		$joinProgramMutation.mutate();
	}

	$: isContactNull = isLoggedIn && !$hasPiggyAccount;
</script>

<section>
	<h2 class="piggy-dashboard__header">
		{#if isLoggedIn}
			{#if isContactNull}
				{getTranslatedText($pluginSettings.dashboard_title_join_program)}
			{:else}
				{getHeaderTitle(
					getTranslatedText($pluginSettings.dashboard_title_logged_in),
					$contactStore?.contact?.balance?.credits ?? 0
				)}
			{/if}
		{:else}
			{getHeaderTitle(getTranslatedText($pluginSettings.dashboard_title_logged_out) ?? '', 400)}
		{/if}
	</h2>

	<!-- Call to action-->
	{#if !isLoggedIn}
		<div class="piggy-dashboard__cta">
			{#if window.piggyWcSettings.storePages.myaccount?.permalink}
				<Button href={window.piggyWcSettings.storePages.myaccount?.permalink} variant="primary">
					{getTranslatedText($pluginSettings.dashboard_join_cta)}
				</Button>

				<Button href={window.piggyWcSettings.storePages.myaccount?.permalink} variant="primary">
					{getTranslatedText($pluginSettings.dashboard_login_cta)}
				</Button>
			{/if}
		</div>
	{:else if isContactNull}
		<div class="piggy-dashboard__cta">
			<Button
				on:click={handleJoinProgram}
				variant="primary"
				loading={$joinProgramMutation.isPending}
			>
				{getTranslatedText($pluginSettings.dashboard_join_program_cta)}
			</Button>

			{#if $joinProgramMutation.isError}
				<p class="piggy-dashboard__error">
					{$joinProgramMutation.error.message}
				</p>
			{/if}
		</div>
	{/if}

	{#if isLoggedIn && !isContactNull}
		<nav class="piggy-dashboard__nav">
			<ul class="piggy-dashboard__list">
				{#each navItems as { icon, text, id }, i}
					<li>
						<button class="piggy-dashboard__item" on:click={handleScrollNavigation(id)}>
							<svelte:component this={icon} size={24} />

							{getNavItemText(text)}
						</button>
					</li>
				{/each}
			</ul>
		</nav>
	{/if}
</section>

<style lang="postcss">
	section {
		text-align: center;
	}

	.piggy-dashboard__header {
		font-size: 1.5rem;
		margin: 0;
		margin-bottom: 1.5rem;
		margin-left: auto;
		margin-right: auto;
		max-width: 450px;
	}

	@media screen and (max-width: 768px) {
		.piggy-dashboard__header {
			font-size: 1.25rem;
		}
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

	.piggy-dashboard__cta {
		display: flex;
		justify-content: center;
		gap: 0.5rem;
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
