<script lang="ts">
	import { createMutation } from '@tanstack/svelte-query';
	import { piggyService } from '$lib/config/services';
	import { creditsName, isLoggedIn, pluginSettings } from '$lib/modules/settings';
	import { MutationKeys } from '$lib/utils/query-keys';
	import { getTranslatedText } from '$lib/utils/translated-text';
	import CheckCircle from 'lucide-svelte/icons/badge-check';
	import { replaceStrings } from '@piggy/lib';
	import type { EarnRuleType, EarnRuleValueItem } from '@piggy/types/plugin/settings/adminTypes';
	import Button from './button/button.svelte';

	export let earnRule: EarnRuleValueItem;

	const socialTypes = [
		'LIKE_ON_FACEBOOK',
		'FOLLOW_ON_INSTAGRAM',
		'FOLLOW_ON_TIKTOK'
	] as EarnRuleType[];

	const claimableOnceTypes = [...socialTypes, 'CREATE_ACCOUNT'];

	const claimRewardMutation = createMutation({
		mutationKey: [MutationKeys.claimReward],
		mutationFn: () => piggyService.claimReward(earnRule.id, window.piggyMiddlewareConfig.userId),
		onSuccess: () => {
			const handle = earnRule.socialHandle.value;

			if (!handle) return;

			const socialLink = getSocialLink(earnRule.type.value, handle);
			if (socialLink) {
				window.open(socialLink, '_blank');
			}
		}
	});

	function getSocialLink(type: EarnRuleType, handle: string): string {
		switch (type) {
			case 'LIKE_ON_FACEBOOK':
				return `https://www.facebook.com/${handle}`;
			case 'FOLLOW_ON_INSTAGRAM':
				return `https://www.instagram.com/${handle}`;
			case 'FOLLOW_ON_TIKTOK':
				return `https://www.tiktok.com/@${handle}`;
			default:
				return '';
		}
	}

	function getLabel(text: string, credits: number | string) {
		if (!text) return '';

		const handle = earnRule.socialHandle.value;

		return replaceStrings(text, [
			{
				'{{ credits_currency }}': $creditsName ?? '',
				'{{ credits }}': credits?.toString() ?? '0',
				'{{ handle }}': handle ? `@${handle}` : ''
			}
		]);
	}

	$: isSocial = socialTypes.includes(earnRule.type.value);
	$: isClaimableOnce = claimableOnceTypes.includes(earnRule.type.value);
	$: hasClaimed = window.piggyData.claimedRewards?.find(
		(reward) => reward.earn_rule_id === earnRule.id.toString() && isClaimableOnce
	);
</script>

<div class="piggy-dashboard-earn-card">
	<div>
		<div class="piggy-dashboard-earn-card__icon">
			{@html earnRule.svg}
		</div>

		<h4 class="piggy-dashboard-earn-card__header">
			{#if earnRule.label.value}
				{@html getLabel(getTranslatedText(earnRule.label.value), earnRule.credits.value ?? 0)}
			{/if}
		</h4>

		{#if isLoggedIn && isSocial}
			<div class="piggy-dashboard-earn-card__action">
				{#if !hasClaimed}
					<Button
						loading={$claimRewardMutation.isPending}
						disabled={$claimRewardMutation.isPending}
						variant="primary"
						on:click={() => $claimRewardMutation.mutateAsync()}
					>
						{getTranslatedText($pluginSettings.dashboard_earn_cta)}
					</Button>

					{#if $claimRewardMutation.isError}
						<div style="color: red; margin-top: 8px; font-size: 13px;">
							{$claimRewardMutation.error.message}
						</div>
					{/if}
				{:else}
					<CheckCircle size="24" color="#3da121" />
				{/if}
			</div>
		{/if}
	</div>
</div>

<style>
	.piggy-dashboard-earn-card {
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
		margin-top: 14px;
		display: flex;
		justify-content: center;
		flex-direction: column;
		width: 100%;
		display: flex;
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
