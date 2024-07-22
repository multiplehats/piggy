<script lang="ts">
	import { currentLanguage, pluginSettings } from '$lib/modules/settings';
	import { replaceStrings } from '@piggy/lib';

	function getNavItemText(text?: string) {
		if (!text) return '';

		const creditsName = $pluginSettings?.credits_name?.[currentLanguage];

		return replaceStrings(text, [{ '{{credits_currency}}': creditsName ?? '' }]);
	}
</script>

<div class="piggy-dashboard-coupons">
	<div>
		<h3 class="piggy-dashboard__header">
			{getNavItemText($pluginSettings?.dashboard_nav_coupons?.[currentLanguage])}
		</h3>
	</div>
</div>

<style>
	.piggy-dashboard-coupons {
		text-align: center;
		max-width: 1260px;
		width: 100%;
		margin-top: 3rem;
	}

	.piggy-dashboard__header {
		font-size: 1.5rem;
		margin: 0;
		margin-bottom: 1.5rem;
		margin-left: auto;
		margin-right: auto;
		max-width: 450px;
	}
</style>
