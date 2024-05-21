<script lang="ts">
	import type {
		PluginAdminConfig,
		PluginOptionsAdmin,
		PluginOptionsAdminKeys
	} from '@piggy/types/plugin';
	import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/svelte-query';
	import Layout from '$lib/components/layout-wrapper.svelte';
	import PageGeneralSettings from '$lib/routes/page-general-settings.svelte';
	import PageHome from '$lib/routes/page-home.svelte';
	import { adminConfigState, settingsState } from '$lib/stores/settings';
	import { history } from '$lib/utils/custom-history';
	import type { SvelteComponent } from 'svelte';
	import { Route, Router } from 'svelte-navigator';
	import '@piggy/tailwind-config/global.postcss';
	import { __ } from '@wordpress/i18n';
	import toast from 'svelte-french-toast';
	import PageOnboarding from './routes/page-onboarding.svelte';

	export let pluginSettings: PluginOptionsAdmin;
	export let adminConfig: PluginAdminConfig;

	const routes = window.wp.hooks.applyFilters('piggy.adminRoutes', [
		{ path: '/', component: PageHome, primary: true },
		{ path: 'general', component: PageGeneralSettings, primary: true },
		{ path: 'onboarding', component: PageOnboarding, primary: false }
	]) as { path: string; component: typeof SvelteComponent; primary: boolean }[];

	adminConfigState.set(adminConfig);

	settingsState.update((current) => {
		const updated = { ...current };

		// Merge the settings from the server with the defaults.
		// If there's for whatever reason no settings, this will just use the defaults.
		if (pluginSettings) {
			for (const key in pluginSettings) {
				if (pluginSettings.hasOwnProperty(key)) {
					// @ts-expect-error - TODO: Fix this
					updated[key] = pluginSettings[key as PluginOptionsAdminKeys];
				}
			}
		}

		return updated;
	});

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
			{#each Object.entries(routes) as [key, { path, component, primary }] (key)}
				<Route {path} let:params {primary}>
					<svelte:component this={component} {params} />
				</Route>
			{/each}
		</Layout>
	</Router>
</QueryClientProvider>
