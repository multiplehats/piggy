<script lang="ts">
	import { createMutation, useQueryClient } from "@tanstack/svelte-query";
	import CheckCircle from "lucide-svelte/icons/badge-check";
	import { claimReward, replaceStrings } from "@leat/lib";
	import type { EarnRuleType, EarnRuleValueItem } from "@leat/types/plugin/settings/adminTypes";
	import { getTranslatedText } from "@leat/i18n";
	import { Button } from "$lib/components/button/index.js";
	import { creditsName, isLoggedIn, pluginSettings } from "$lib/modules/settings";
	import { contactStore, hasLeatAccount } from "$lib/stores";
	import { MutationKeys, QueryKeys } from "$lib/utils/query-keys";

	export let earnRule: EarnRuleValueItem;

	let socialLinkToOpen: string | null = null;

	const socialTypes = [
		"LIKE_ON_FACEBOOK",
		"FOLLOW_ON_INSTAGRAM",
		"FOLLOW_ON_TIKTOK",
	] as EarnRuleType[];

	const claimableOnceTypes = [...socialTypes, "CREATE_ACCOUNT"];
	const queryClient = useQueryClient();
	const claimRewardMutation = createMutation({
		mutationKey: [MutationKeys.claimReward],
		mutationFn: () => claimReward(earnRule.id, window.leatMiddlewareConfig.userId),
		onSuccess: () => {
			const handle = earnRule.socialHandle.value;
			if (handle) {
				socialLinkToOpen = getSocialLink(earnRule.type.value, handle);
			}

			queryClient.invalidateQueries({ queryKey: [QueryKeys.contact] });
		},
	});

	function getSocialLink(type: EarnRuleType, handle: string): string {
		switch (type) {
			case "LIKE_ON_FACEBOOK":
				return `https://www.facebook.com/${handle}`;
			case "FOLLOW_ON_INSTAGRAM":
				return `https://www.instagram.com/${handle}`;
			case "FOLLOW_ON_TIKTOK":
				return `https://www.tiktok.com/@${handle}`;
			default:
				return "";
		}
	}

	function getLabel(text: string, credits: number | string) {
		if (!text) return "";

		const handle = earnRule.socialHandle.value;

		return replaceStrings(text, [
			{
				"{{ credits_currency }}": $creditsName ?? "",
				"{{ credits }}": credits?.toString() ?? "0",
				"{{ handle }}": handle ? `@${handle}` : "",
			},
		]);
	}

	function handleClaimAndOpenLink() {
		$claimRewardMutation.mutateAsync().then(() => {
			if (socialLinkToOpen) {
				window.open(socialLinkToOpen, "_blank");
				socialLinkToOpen = null;
			}
		});
	}

	$: isSocial = socialTypes.includes(earnRule.type.value);
	$: isClaimableOnce = claimableOnceTypes.includes(earnRule.type.value);

	$: hasClaimed = $contactStore?.claimedRewards?.find(
		(reward) => reward.earn_rule_id === earnRule.id.toString() && isClaimableOnce
	);
</script>

<div class="leat-dashboard-earn-card">
	<div>
		<div class="leat-dashboard-earn-card__icon">
			<!--  eslint-disable-next-line svelte/no-at-html-tags -->
			{@html earnRule.svg}
		</div>

		<h4 class="leat-dashboard-earn-card__header">
			{#if earnRule.label.value}
				<!--  eslint-disable-next-line svelte/no-at-html-tags -->
				{@html getLabel(
					getTranslatedText(earnRule.label.value),
					earnRule.credits.value ?? 0
				)}
			{/if}
		</h4>

		{#if isLoggedIn && isSocial && $hasLeatAccount}
			<div class="leat-dashboard-earn-card__action">
				{#if !hasClaimed}
					<Button
						loading={$claimRewardMutation.isPending}
						disabled={$claimRewardMutation.isPending}
						variant="primary"
						on:click={handleClaimAndOpenLink}
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
	.leat-dashboard-earn-card {
		border-radius: 0.375rem;
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
		margin-top: 14px;
		display: flex;
		justify-content: center;
		flex-direction: column;
		width: 100%;
		display: flex;
		align-items: center;
	}

	.leat-dashboard-earn-card__icon {
		width: 100%;
		height: auto;
	}

	h4.leat-dashboard-earn-card__header {
		font-size: 1rem;
		margin: 0.5rem 0 0 0;
	}
</style>
