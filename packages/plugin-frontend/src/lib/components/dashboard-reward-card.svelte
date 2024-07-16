<script lang="ts">
	import { pluginSettings } from '$lib/modules/settings';
	import { getTranslatedText } from '$lib/utils/translated-text';
	import { replaceStrings } from '@piggy/lib';
	import type { SpendRuleValueItem } from '@piggy/types/plugin/settings/adminTypes';

	export let rule: SpendRuleValueItem;

	function getLabel(text: string, credits: number | string) {
		if (!text) return '';

		const creditsName = getTranslatedText($pluginSettings.credits_name);

		return replaceStrings(text, [
			{
				'{{ credits_currency }}': creditsName ?? '',
				'{{ credits }}': credits?.toString() ?? '0'
			}
		]);
	}

	$: console.log(rule);
</script>

<div class="piggy-dashboard-earn-card">
	<div>
		<h4 class="piggy-dashboard__header">
			{#if rule.label.value}
				{getLabel(getTranslatedText(rule.label.value), 0)}
			{/if}
		</h4>
	</div>
</div>

<style>
	.piggy-dashboard-earn-card {
		background-color: var(--piggy-dashboard-card-background-color, #fff);
		padding: 24px;
		text-align: center;
		box-shadow:
			0 0 #0000,
			0 0 #0000,
			0 1px 3px 0 rgb(0 0 0 / 0.1),
			0 1px 2px -1px rgb(0 0 0 / 0.1);
	}

	.piggy-dashboard-earn-card h4 {
		font-size: 1rem;
		margin: 0;
	}
</style>
