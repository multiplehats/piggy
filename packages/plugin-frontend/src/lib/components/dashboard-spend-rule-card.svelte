<script lang="ts">
	import { createMutation, useQueryClient } from "@tanstack/svelte-query";
	import Gift from "lucide-svelte/icons/gift";
	import { cubicOut } from "svelte/easing";
	import { tweened } from "svelte/motion";
	import { claimSpendRule, replaceStrings } from "@leat/lib";
	import type { SpendRuleValueItem } from "@leat/types/plugin/settings/adminTypes";
	import { __ } from "@wordpress/i18n";
	import { getSpendRuleLabel, getTranslatedText } from "@leat/i18n";
	import { Button } from "$lib/components/button/index.js";
	import { MutationKeys, QueryKeys } from "$lib/utils/query-keys";
	import { contactStore, hasLeatAccount } from "$lib/stores";
	import { creditsName, isLoggedIn, pluginSettings } from "$lib/modules/settings";

	export let rule: SpendRuleValueItem;

	const progress = tweened(0, {
		duration: 450,
		easing: cubicOut,
	});

	const client = useQueryClient();
	const claimSpendRuleMutation = createMutation({
		mutationKey: [MutationKeys.claimSpendRule],
		mutationFn: () => handleClaim(rule.id),
		onSuccess: async () => {
			await client.invalidateQueries({ queryKey: [QueryKeys.coupons] });
			await client.refetchQueries({ queryKey: [QueryKeys.coupons] });

			// Scroll to coupons section after successful claim
			const couponsSection = document.querySelector(".leat-dashboard-coupons");
			if (couponsSection) {
				couponsSection.scrollIntoView({ behavior: "smooth" });
			}
		},
	});

	function handleClaim(id: number) {
		return claimSpendRule(id, window.leatMiddlewareConfig.userId);
	}

	$: creditsAccumulated = $contactStore?.contact?.balance?.credits ?? 0;
	$: creditsRequired = rule.creditCost.value;
	$: if (creditsRequired) {
		progress.set(creditsAccumulated / creditsRequired);
	}

	$: hasEnoughCredits = creditsAccumulated >= (creditsRequired ?? 0);

	function getDescription(text: string, credits: number | string | null) {
		if (!text) return "";

		return replaceStrings(text, [
			{
				"{{ credits_currency }}": $creditsName ?? "",
				"{{ credits }}": credits?.toString() ?? "0",
				"{{ discount }}": rule.discountValue?.value?.toString() ?? "0",
			},
		]);
	}

	function getProgressText(text: string, creditsRecuired: number | string) {
		if (!text) return "";

		return replaceStrings(text, [
			{
				"{{ credits }}": creditsAccumulated?.toString() ?? "0",
				"{{ credits_currency }}": $creditsName ?? "",
				"{{ credits_required }}": creditsRecuired?.toString() ?? "0",
			},
		]);
	}
</script>

<div class="leat-dashboard-reward-card">
	{#if creditsRequired}
		<div class="leat-dashboard-reward-card__badge">
			{creditsRequired}
		</div>
	{/if}

	<div class="leat-dashboard-reward-card__icon">
		{#if rule?.image?.value}
			<img src={rule.image.value} alt={__("Reward image", "leat-crm")} />
		{:else}
			<Gift size={48} />
		{/if}
	</div>

	<h4 class="leat-dashboard-reward-card__header">
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

	{#if rule.description.value}
		<p class="leat-dashboard-reward-card__description">
			{getDescription(getTranslatedText(rule.description.value), creditsRequired)}
		</p>
	{/if}

	{#if creditsRequired && isLoggedIn}
		<div class="leat-dashboard-reward-card__progress">
			<progress value={$progress} />

			{#if $pluginSettings.credits_spend_rule_progress && creditsAccumulated}
				<p>
					{getProgressText(
						getTranslatedText($pluginSettings.credits_spend_rule_progress),
						creditsRequired
					)}
				</p>
			{/if}
		</div>
	{/if}

	{#if isLoggedIn && $hasLeatAccount}
		<div class="leat-dashboard-earn-card__action">
			<Button
				loading={$claimSpendRuleMutation.isPending}
				disabled={$claimSpendRuleMutation.isPending || !hasEnoughCredits}
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
	.leat-dashboard-reward-card {
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

	.leat-dashboard-earn-card__action {
		margin-top: 12px;
	}

	.leat-dashboard-reward-card__badge {
		position: absolute;
		top: 0;
		right: 0;
		background-color: var(--leat-dashboard-card-badge-background-color, #25a418);
		color: var(--leat-dashboard-card-badge-color, #fff);
		padding: 0.25rem 0.5rem;
		border-radius: 0 0 0 5px;
		font-size: 0.75rem;
	}

	.leat-dashboard-reward-card__icon {
		width: 100%;
		height: 80px;
		display: flex;
		justify-content: center;
		align-items: center;
		margin-bottom: 0.25rem;
	}

	.leat-dashboard-reward-card__icon img {
		max-width: 100%;
		max-height: 100%;
		object-fit: contain;
		border-radius: 0.375rem;
		box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
	}

	h4.leat-dashboard-reward-card__header {
		font-size: 1rem;
		margin: 0.5rem 0 0 0;
	}

	.leat-dashboard-reward-card__description {
		font-size: 0.675rem;
		margin: 0.1rem 0 0 0;
	}

	.leat-dashboard-reward-card__progress p {
		font-size: 0.575rem;
		margin: 0;
		font-weight: 500;
		text-transform: uppercase;
		letter-spacing: 0.05em;
	}

	progress {
		width: 100%;
		height: var(--leat-reward-meter-height, 5px);
		border-radius: 5px;
		overflow: hidden;
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
	}

	/* background: */
	progress::-webkit-progress-bar {
		background-color: var(--leat-reward-meter-background, #dedde0);
		width: 100%;
	}

	progress {
		background-color: var(--leat-reward-meter-background, #dedde0);
	}

	/* value: */
	progress::-webkit-progress-value {
		background-color: var(--leat-reward-meter-background-active, #25a418) !important;
	}
	progress::-moz-progress-bar {
		background-color: var(--leat-reward-meter-background-active, #25a418) !important;
	}
	progress {
		color: var(--leat-reward-meter-background-active, #25a418);
	}
</style>
