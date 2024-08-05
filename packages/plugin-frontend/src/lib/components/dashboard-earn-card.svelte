<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { piggyService } from '$lib/config/services';
	import { pluginSettings } from '$lib/modules/settings';
	import { MutationKeys } from '$lib/utils/query-keys';
	import { getTranslatedText } from '$lib/utils/translated-text';
	import { replaceStrings } from '@piggy/lib';
	import type { EarnRuleType, EarnRuleValueItem } from '@piggy/types/plugin/settings/adminTypes';
	import Button from './button/button.svelte';

	export let earnRule: EarnRuleValueItem;

	const claimRewardMutation = createMutation({
		mutationKey: [MutationKeys.claimReward],
		mutationFn: () => handleClaim(earnRule.id),
		onSuccess: () => {
			const handle = earnRule.socialHandle.value;

			if (earnRule.type.value === 'LIKE_ON_FACEBOOK') {
				window.open(`https://www.facebook.com/${handle}`, '_blank');
			}

			if (earnRule.type.value === 'FOLLOW_ON_INSTAGRAM') {
				window.open(`https://www.instagram.com/${handle}`, '_blank');
			}

			if (earnRule.type.value === 'FOLLOW_ON_TIKTOK') {
				window.open(`https://www.tiktok.com/@${handle}`, '_blank');
			}
		}
	});

	async function handleClaim(id: number) {
		return await piggyService.claimReward(id, window.piggyMiddlewareConfig.userId);
	}

	function getLabel(text: string, credits: number | string) {
		if (!text) return '';

		const creditsName = getTranslatedText($pluginSettings.credits_name);
		const handle = earnRule.socialHandle.value;

		return replaceStrings(text, [
			{
				'{{ credits_currency }}': creditsName ?? '',
				'{{ credits }}': credits?.toString() ?? '0',
				'{{ handle }}': `@${handle}` ?? ''
			}
		]);
	}

	const socialTypes = [
		'LIKE_ON_FACEBOOK',
		'FOLLOW_ON_INSTAGRAM',
		'FOLLOW_ON_TIKTOK'
	] as EarnRuleType[];

	$: isSocial = socialTypes.includes(earnRule.type.value);
</script>

<div class="piggy-dashboard-earn-card">
	<div>
		<div class="piggy-dashboard-earn-card__icon">
			{@html earnRule.svg}
		</div>

		<h4 class="piggy-dashboard-earn-card__header">
			{#if earnRule.label.value}
				{getLabel(getTranslatedText(earnRule.label.value), earnRule.credits.value ?? 0)}
			{/if}
		</h4>

		{#if isSocial}
			<div class="piggy-dashboard-earn-card__action">
				<Button
					loading={$claimRewardMutation.isPending}
					disabled={$claimRewardMutation.isPending}
					variant="primary"
					on:click={() => $claimRewardMutation.mutateAsync()}
				>
					Claim
				</Button>

				{#if $claimRewardMutation.isError}
					<div style="color: red; margin-top: 8px; font-size: 13px;">
						{$claimRewardMutation.error.message}
					</div>
				{/if}
			</div>
		{/if}
	</div>
</div>

<style>
	.piggy-dashboard-earn-card {
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: start;
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
		margin-top: 14px;
		display: flex;
		justify-content: center;
		flex-direction: column;
		width: 100%;
		display: flex;
		justify-content: center;
		align-items: center;
	}

	.piggy-dashboard-earn-card__icon {
		width: 100%;
		height: auto;
	}

	h4.piggy-dashboard-earn-card__header {
		font-size: 1rem;
		margin: 0.5rem 0 0 0;
	}
</style>
