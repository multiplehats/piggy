<script lang="ts">
	import { pluginSettings } from '$lib/modules/settings';
	import { getTranslatedText } from '$lib/utils/translated-text';
	import { cubicOut } from 'svelte/easing';
	import { tweened } from 'svelte/motion';
	import { replaceStrings } from '@piggy/lib';
	import type { SpendRuleValueItem } from '@piggy/types/plugin/settings/adminTypes';

	export let rule: SpendRuleValueItem;

	const progress = tweened(0, {
		duration: 450,
		easing: cubicOut
	});

	$: creditsName = getTranslatedText($pluginSettings.credits_name);
	$: creditsAccumulated = window.piggyData.contact?.balance.credits ?? 0;
	$: creditsRequired = rule.creditCost.value;
	$: if (creditsRequired) {
		progress.set(creditsAccumulated / creditsRequired);
	}

	function getLabel(text: string, credits: number | string) {
		if (!text) return '';

		return replaceStrings(text, [
			{
				'{{ credits_currency }}': creditsName ?? '',
				'{{ credits }}': credits?.toString() ?? '0',
				'{{ discount }}': rule.discountValue?.value?.toString() ?? '0'
			}
		]);
	}

	function getDescription(text: string, credits: number | string) {
		if (!text) return '';

		return replaceStrings(text, [
			{
				'{{ credits_currency }}': creditsName ?? '',
				'{{ credits }}': credits?.toString() ?? '0',
				'{{ discount }}': rule.discountValue?.value?.toString() ?? '0'
			}
		]);
	}

	function getProgressText(text: string, creditsRecuired: number | string) {
		if (!text) return '';

		return replaceStrings(text, [
			{
				'{{ credits }}': creditsAccumulated?.toString() ?? '0',
				'{{ credits_currency }}': creditsName ?? '',
				'{{ credits_required }}': creditsRecuired?.toString() ?? '0'
			}
		]);
	}
</script>

<div class="piggy-dashboard-reward-card">
	<!-- Credits required badge-->
	{#if creditsRequired}
		<div class="piggy-dashboard-reward-card__badge">
			{creditsRequired}
		</div>
	{/if}

	<div class="piggy-dashboard-reward-card__icon">
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="48"
			height="48"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			stroke-width="2"
			stroke-linecap="round"
			stroke-linejoin="round"
			class="widget__icons-color"
			><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z"
			></path><path d="M12 8l0 13"></path><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7"
			></path><path
				d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5"
			></path></svg
		>
	</div>

	<h4 class="piggy-dashboard-reward-card__header">
		{#if rule.label.value}
			{getLabel(getTranslatedText(rule.label.value), 0)}
		{/if}
	</h4>

	<p class="piggy-dashboard-reward-card__description">
		{#if rule.description.value}
			{getDescription(getTranslatedText(rule.description.value), creditsRequired)}
		{/if}
	</p>

	{#if creditsRequired}
		<div class="piggy-dashboard-reward-card__progress">
			<progress value={$progress} />

			{#if $pluginSettings.credits_spend_rule_progress}
				<p>
					{getProgressText(
						getTranslatedText($pluginSettings.credits_spend_rule_progress),
						creditsRequired
					)}
				</p>
			{/if}
		</div>
	{/if}
</div>

<style>
	.piggy-dashboard-reward-card {
		position: relative;
		background-color: var(--piggy-dashboard-card-background-color, #fff);
		padding: 24px;
		text-align: center;
		box-shadow:
			0 0 #0000,
			0 0 #0000,
			0 1px 3px 0 rgb(0 0 0 / 0.1),
			0 1px 2px -1px rgb(0 0 0 / 0.1);
	}

	.piggy-dashboard-reward-card__badge {
		position: absolute;
		top: 0;
		right: 0;
		background-color: var(--piggy-dashboard-card-badge-background-color, #25a418);
		color: var(--piggy-dashboard-card-badge-color, #fff);
		padding: 0.25rem 0.5rem;
		border-radius: 0 0 0 5px;
		font-size: 0.75rem;
	}

	.piggy-dashboard-reward-card__icon {
		width: 100%;
		height: auto;
	}

	h4.piggy-dashboard-reward-card__header {
		font-size: 1rem;
		margin: 0.5rem 0 0 0;
	}

	.piggy-dashboard-reward-card__description {
		font-size: 0.675rem;
		margin: 0.1rem 0 0 0;
	}

	.piggy-dashboard-reward-card__progress p {
		font-size: 0.575rem;
		margin: 0;
		font-weight: 500;
		text-transform: uppercase;
		letter-spacing: 0.05em;
	}

	progress {
		width: 100%;
		height: var(--piggy-reward-meter-height, 5px);
		border-radius: 5px;
		overflow: hidden;
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
	}

	/* background: */
	progress::-webkit-progress-bar {
		background-color: var(--piggy-reward-meter-background, #dedde0);
		width: 100%;
	}

	progress {
		background-color: var(--piggy-reward-meter-background, #dedde0);
	}

	/* value: */
	progress::-webkit-progress-value {
		background-color: var(--piggy-reward-meter-background-active, #25a418) !important;
	}
	progress::-moz-progress-bar {
		background-color: var(--piggy-reward-meter-background-active, #25a418) !important;
	}
	progress {
		color: var(--piggy-reward-meter-background-active, #25a418);
	}
</style>
