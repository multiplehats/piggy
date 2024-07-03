<script lang="ts">
	import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/svelte-query';
	import Layout from '$lib/components/layout-wrapper.svelte';
	import PageGeneralSettings from '$lib/routes/page-general-settings.svelte';
	import PageHome from '$lib/routes/page-home.svelte';
	import { history } from '$lib/utils/custom-history';
	import { Route, Router } from 'svelte-navigator';
	import '@piggy/tailwind-config/global.postcss';
	import { __ } from '@wordpress/i18n';
	import toast from 'svelte-french-toast';
	import PageEarnRulesId from './routes/page-earn-rules-id.svelte';
	import PageLoyaltyProgram from './routes/page-loyalty-program.svelte';
	import PageOnboarding from './routes/page-onboarding.svelte';
	import PageSpendRulesId from './routes/page-spend-rules-id.svelte';

	const mutationCache = new MutationCache({
		onError: (error) => {
			toast.error(__('There was an unknown error.', 'piggy'));
		}
	});

	const queryClient = new QueryClient({
		mutationCache,
		defaultOptions: {
			queries: {
				staleTime: 2 * 60 * 1000
			}
		}
	});
</script>

<QueryClientProvider client={queryClient}>
	<Router {history}>
		<Layout>
			<!-- Render the Home component at / -->
			<Route path="/" component={PageHome} />

			<!-- Render the General Settings component at /general -->
			<Route path="general" component={PageGeneralSettings} />

			<!-- Render the Onboarding component at /onboarding -->
			<Route path="onboarding" component={PageOnboarding} />

			<!-- Render the Loyalty Program component at / -->
			<Route path="loyalty-program/*">
				<!-- Render specific earn-rules with id "123" at /loyalty-program/earn-rules/123 -->
				<Route path="earn-rules/:id" component={PageEarnRulesId} />
				<Route path="spend-rules/:id" component={PageSpendRulesId} />

				<!-- Index Route for /loyalty-program -->
				<Route path="/" component={PageLoyaltyProgram} />
			</Route>
		</Layout>
	</Router>
</QueryClientProvider>
