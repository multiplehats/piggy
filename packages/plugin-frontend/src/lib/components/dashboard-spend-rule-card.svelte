<script lang="ts">
	import { createMutation } from '@tanstack/svelte-query';
	import { piggyService } from '$lib/config/services';
	import { creditsName, isLoggedIn, pluginSettings } from '$lib/modules/settings';
	import { contactStore, hasPiggyAccount } from '$lib/stores';
	import { MutationKeys } from '$lib/utils/query-keys';
	import { getSpendRuleLabel, getTranslatedText } from '$lib/utils/translated-text';
	import Gift from 'lucide-svelte/icons/gift';
	import { cubicOut } from 'svelte/easing';
	import { tweened } from 'svelte/motion';
	import { replaceStrings } from '@piggy/lib';
	import type { SpendRuleValueItem } from '@piggy/types/plugin/settings/adminTypes';
	import Button from './button/button.svelte';

	export let rule: SpendRuleValueItem;

	const progress = tweened(0, {
		duration: 450,
		easing: cubicOut
	});

	const claimSpendRuleMutation = createMutation({
		mutationKey: [MutationKeys.claimSpendRule],
		mutationFn: () => handleClaim(rule.id)
	});

	function handleClaim(id: number) {
		return piggyService.claimSpendRule(id, window.piggyMiddlewareConfig.userId);
	}

	$: creditsAccumulated = $contactStore?.contact?.balance?.credits ?? 0;
	$: creditsRequired = rule.creditCost.value;
	$: if (creditsRequired) {
		progress.set(creditsAccumulated / creditsRequired);
	}

	function getDescription(text: string, credits: number | string | null) {
		if (!text) return '';

		return replaceStrings(text, [
			{
				'{{ credits_currency }}': $creditsName ?? '',
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
				'{{ credits_currency }}': $creditsName ?? '',
				'{{ credits_required }}': creditsRecuired?.toString() ?? '0'
			}
		]);
	}
</script>

<div class="piggy-dashboard-reward-card">
	{#if creditsRequired}
		<div class="piggy-dashboard-reward-card__badge">
			{creditsRequired}
		</div>
	{/if}

	<div class="piggy-dashboard-reward-card__icon">
		<Gift size={48} />
	</div>

	<h4 class="piggy-dashboard-reward-card__header">
		{#if rule.label.value}
			{getSpendRuleLabel(
				getTranslatedText(rule.label.value),
				rule.creditCost.value,
				$creditsName,
				rule.discountValue.value,
				rule.discountType.value
			)}
		{/if}
	</h4>

	<p class="piggy-dashboard-reward-card__description">
		{#if rule.description.value}
			{getDescription(getTranslatedText(rule.description.value), creditsRequired)}
		{/if}
	</p>

	{#if creditsRequired && isLoggedIn}
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

	{#if isLoggedIn && $hasPiggyAccount}
		<div class="piggy-dashboard-earn-card__action">
			<Button
				loading={$claimSpendRuleMutation.isPending}
				disabled={$claimSpendRuleMutation.isPending}
				variant="primary"
				on:click={() => $claimSpendRuleMutation.mutateAsync()}
			>
				{getTranslatedText($pluginSettings.dashboard_spend_cta)}
			</Button>

			{#if $claimSpendRuleMutation.isError}
				<div style="color: red; margin-top: 8px; font-size: 13px;">
					{$claimSpendRuleMutation.error.message}
				</div>
			{/if}
		</div>
	{/if}
</div>

<style>
	.piggy-dashboard-reward-card {
		position: relative;
		display: flex;
		flex-direction: column;
		justify-content: flex-start;
		align-items: center;
		background-color: var(--piggy-dashboard-card-background-color, #fff);
		padding: 24px;
		text-align: center;
		box-shadow:
			0 0 #0000,
			0 0 #0000,
			0 1px 3px 0 rgb(0 0 0 / 0.1),
			0 1px 2px -1px rgb(0 0 0 / 0.1);
	}

	.piggy-dashboard-earn-card__action {
		margin-top: 12px;
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
