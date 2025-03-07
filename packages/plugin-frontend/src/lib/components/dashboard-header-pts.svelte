<script lang="ts">
	import { createMutation } from "@tanstack/svelte-query";
	import { replaceStrings } from "@leat/lib";
	import { Button } from "$lib/components/button/index.js";
	import { leatService } from "$lib/config/services";
	import { isLoggedIn, pluginSettings, wcSettings } from "$lib/modules/settings";
	import { contactStore, hasLeatAccount } from "$lib/stores";
	import { MutationKeys } from "$lib/utils/query-keys";
	import { getTranslatedText } from "$lib/utils/translated-text";

	export let navItems: { icon: any; id: string; text: string; show: boolean }[];

	const joinProgramMutation = createMutation({
		mutationKey: [MutationKeys.joinProgram],
		mutationFn: () => leatService.joinProgram(window.leatMiddlewareConfig.userId),
		onSuccess: () => {
			location.reload();
		},
	});

	function getHeaderTitle(text: string, credits: number | string) {
		if (!text) return { before: "", credits: "", after: "" };

		const creditsName = getTranslatedText($pluginSettings?.credits_name);

		// eslint-disable-next-line regexp/no-super-linear-backtracking
		const pattern = /^(.*?)\s*\{\{\s*credits\s*\}\}\s*(.*)$/;
		const match = text.match(pattern);

		if (!match) {
			return { before: text, credits: credits?.toString() ?? "0", after: "" };
		}

		return {
			before: `${replaceStrings(match[1], [{ "{{credits_currency}}": creditsName ?? "" }]).trim()} `,
			credits: credits?.toString() ?? "0",
			after: ` ${replaceStrings(match[2] || "", [{ "{{credits_currency}}": creditsName ?? "" }]).trim()}`,
		};
	}

	function getNavItemText(text?: string) {
		if (!text) return "";

		const creditsName = getTranslatedText($pluginSettings?.credits_name);

		return replaceStrings(text, [{ "{{credits_currency}}": creditsName ?? "" }]);
	}

	function handleScrollNavigation(id: string) {
		// scroll to the nearest `leat-dashboard-${id}` element
		return () => {
			const element = document.querySelector(`.leat-dashboard-${id}`);

			if (element) {
				element.scrollIntoView({ behavior: "smooth" });
			}
		};
	}

	function handleJoinProgram() {
		$joinProgramMutation.mutate();
	}

	$: isContactNull = isLoggedIn && !$hasLeatAccount;
</script>

<section>
	<h2 class="leat-dashboard__header">
		{#if isLoggedIn}
			{#if isContactNull}
				{getTranslatedText($pluginSettings.dashboard_title_join_program)}
			{:else}
				{@const title = getHeaderTitle(
					getTranslatedText($pluginSettings.dashboard_title_logged_in),
					$contactStore?.contact?.balance?.credits ?? 0
				)}
				{#if title.credits}
					{title.before}<span class="leat-credits">{title.credits}</span>{title.after}
				{:else}
					{title.before}
				{/if}
			{/if}
		{:else}
			{@const title = getHeaderTitle(
				getTranslatedText($pluginSettings.dashboard_title_logged_out) ?? "",
				400
			)}
			{#if title.credits && title.after}
				{title.before}<span class="leat-credits">{title.credits}</span>{title.after}
			{:else}
				{title.before}
			{/if}
		{/if}
	</h2>

	<!-- Call to action-->
	{#if !isLoggedIn}
		<div class="leat-dashboard__cta">
			{#if window.leatWcSettings.storePages.leat_dashboard?.permalink}
				{#if $pluginSettings.dashboard_show_join_program_cta === "on" && $wcSettings.canUserRegister}
					<Button
						href={window.leatWcSettings.storePages.leat_dashboard?.permalink}
						variant="primary"
					>
						{getTranslatedText($pluginSettings.dashboard_join_cta)}
					</Button>
				{/if}

				<Button
					href={window.leatWcSettings.storePages.leat_dashboard?.permalink}
					variant="primary"
				>
					{getTranslatedText($pluginSettings.dashboard_login_cta)}
				</Button>
			{/if}
		</div>
	{:else if isContactNull}
		<div class="leat-dashboard__cta">
			{#if $pluginSettings.dashboard_show_join_program_cta === "on" && $wcSettings.canUserRegister}
				<Button
					on:click={handleJoinProgram}
					variant="primary"
					loading={$joinProgramMutation.isPending}
				>
					{getTranslatedText($pluginSettings.dashboard_join_program_cta)}
				</Button>
			{/if}

			{#if $joinProgramMutation.isError}
				<p class="leat-dashboard__error">
					{$joinProgramMutation.error.message}
				</p>
			{/if}
		</div>
	{/if}

	{#if isLoggedIn && !isContactNull}
		<nav class="leat-dashboard__nav">
			<ul class="leat-dashboard__list">
				{#each navItems as { icon, text, id, show }}
					{#if show}
						<li>
							<button
								class="leat-dashboard__item"
								on:click={handleScrollNavigation(id)}
							>
								<svelte:component this={icon} size={24} />

								{getNavItemText(text)}
							</button>
						</li>
					{/if}
				{/each}
			</ul>
		</nav>
	{/if}
</section>

<style lang="postcss">
	section {
		text-align: center;
	}

	.leat-dashboard__nav {
		justify-content: center;
		display: flex;
		align-items: center;
	}

	.leat-dashboard__header {
		font-weight: 700;
		font-size: 1.5rem;
	}

	@media screen and (max-width: 768px) {
		.leat-dashboard__header {
			font-size: 1.25rem;
		}
	}

	.leat-dashboard__list {
		display: inline-flex;
		border-bottom: 1px solid var(--leat-dashboard-nav-item-border, #e5e5e5);
		gap: 6px;
		justify-content: center;
		flex-wrap: wrap;
		list-style-type: none;
		margin: 0;
	}

	.leat-dashboard__cta {
		display: flex;
		justify-content: center;
		gap: 0.5rem;
	}

	.leat-dashboard__item {
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

	.leat-dashboard__item:hover {
		background-color: var(--leat-dashboard-nav-item-bg-hover, #f5f5f5);
	}

	@media (max-width: 768px) {
		.leat-dashboard__list {
			border-bottom: none;
			width: 100%;
			display: grid;
			grid-template-columns: repeat(1, 1fr);
		}

		.leat-dashboard__item {
			border-radius: 6px;
			background-color: var(--leat-dashboard-nav-item-bg, #f5f5f5);
			width: 100%;
		}
	}
</style>
