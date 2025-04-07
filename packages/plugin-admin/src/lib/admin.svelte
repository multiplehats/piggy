<script lang="ts">
	import { MutationCache, QueryClient, QueryClientProvider } from "@tanstack/svelte-query";
	import { Route, Router } from "svelte-navigator";
	import PageDashboardSettings from "./routes/page-dashboard-settings.svelte";
	import PageEarnRulesId from "./routes/page-earn-rules-id.svelte";
	import PageLoyaltyProgram from "./routes/page-loyalty-program.svelte";
	import PagePromotions from "./routes/page-promotions.svelte";
	import PageOnboarding from "./routes/page-onboarding.svelte";
	import PageSpendRulesId from "./routes/page-spend-rules-id.svelte";
	import PageGiftcardSettings from "./routes/page-giftcard-settings.svelte";
	import PagePromotionRulesId from "./routes/page-promotion-rules-id.svelte";
	import PageWebhooks from "./routes/page-webhooks.svelte";
	import { history } from "$lib/utils/custom-history";
	import Layout from "$lib/components/layout-wrapper.svelte";
	import PageGeneralSettings from "$lib/routes/page-general-settings.svelte";
	import "@leat/tailwind-config/global.postcss";
	import PagePrepaidSettings from "./routes/page-prepaid-settings.svelte";

	const mutationCache = new MutationCache();

	const queryClient = new QueryClient({
		mutationCache,
		defaultOptions: {
			queries: {
				staleTime: 2 * 60 * 1000,
			},
		},
	});
</script>

<QueryClientProvider client={queryClient}>
	<Router {history}>
		<Layout>
			<!-- Render the Home component at / -->
			<Route path="/" component={PageGeneralSettings} />

			<Route path="general" component={PageGeneralSettings} />

			<Route path="dashboard" component={PageDashboardSettings} />

			<Route path="giftcards" component={PageGiftcardSettings} />

			<Route path="prepaid" component={PagePrepaidSettings} />

			<Route path="loyalty-program/*">
				<Route path="earn-rules/:id" component={PageEarnRulesId} />
				<Route path="spend-rules/:id" component={PageSpendRulesId} />

				<Route path="/" component={PageLoyaltyProgram} />
			</Route>

			<Route path="promotions/*">
				<Route path="promotion-rules/:id" component={PagePromotionRulesId} />

				<Route path="/" component={PagePromotions} />
			</Route>

			<Route path="webhooks" component={PageWebhooks} />

			<Route path="onboarding" component={PageOnboarding} />
		</Layout>
	</Router>
</QueryClientProvider>
