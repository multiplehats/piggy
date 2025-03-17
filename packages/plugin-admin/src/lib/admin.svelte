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

			<!-- Render the General Settings component at /general -->
			<Route path="general" component={PageGeneralSettings} />

			<!-- Render the Dashboard Settings component at /dashboard -->
			<Route path="dashboard" component={PageDashboardSettings} />

			<!-- Render the Giftcard Settings component at /giftcards -->
			<Route path="giftcards" component={PageGiftcardSettings} />

			<!-- Render the Loyalty Program component at / -->
			<Route path="loyalty-program/*">
				<!-- Render specific earn-rules with id "123" at /loyalty-program/earn-rules/123 -->
				<Route path="earn-rules/:id" component={PageEarnRulesId} />
				<Route path="spend-rules/:id" component={PageSpendRulesId} />

				<!-- Index Route for /loyalty-program -->
				<Route path="/" component={PageLoyaltyProgram} />
			</Route>

			<!-- Render the Promotions component at / -->
			<Route path="promotions/*">
				<!-- Render specific earn-rules with id "123" at /promotions/earn-rules/123 -->
				<Route path="promotion-rules/:id" component={PagePromotionRulesId} />

				<!-- Index Route for /promotions -->
				<Route path="/" component={PagePromotions} />
			</Route>

			<!-- Render the Webhooks component at /webhooks -->
			<Route path="webhooks" component={PageWebhooks} />

			<!-- Render the Onboarding component at /onboarding -->
			<Route path="onboarding" component={PageOnboarding} />
		</Layout>
	</Router>
</QueryClientProvider>
