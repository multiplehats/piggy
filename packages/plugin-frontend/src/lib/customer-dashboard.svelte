<script lang="ts">
	import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/svelte-query';
	import { SvelteQueryDevtools } from '@tanstack/svelte-query-devtools';
	import { __ } from '@wordpress/i18n';
	import { isDev } from '$lib/config/environment';
	import { WooCommerceStoreApiError } from '$lib/modules/cart/cart.service';
	import {
		pluginSettings as settingsState,
		wcSettings as wcSettingsState
	} from '$lib/modules/settings';
	import { hooks } from '$lib/utils/hooks';
	import { onMount } from 'svelte';
	import type { IWooSettings, PluginOptionsFrontend } from '@piggy/types/plugin';
	import DashboardHeaderPts from './components/dashboard-header-pts.svelte';

	export let wcSettings: IWooSettings;
	export let pluginSettings: PluginOptionsFrontend;

	// Hydrate the state with the settings passed from the server.
	wcSettingsState.set(wcSettings);
	settingsState.set(pluginSettings);

	const mutationCache = new MutationCache({
		onError: (error) => {
			if (error instanceof WooCommerceStoreApiError) {
				const { message, status, statusText } = error;

				// toast.error(message, {}, `${status} ${statusText}`);

				return;
			}

			// toast.error(__('There was an unknown error.', 'piggy'));
		}
	});

	onMount(async () => {
		hooks.doAction('on.init', $settingsState);
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
	{#if $settingsState}
		{#if $settingsState.plugin_enable}
			<div class="piggy-dashboard">
				<DashboardHeaderPts />
			</div>
		{/if}
	{/if}

	{#if isDev}
		<SvelteQueryDevtools />
	{/if}
</QueryClientProvider>

<style>
	.piggy-dashboard {
		background-color: white;
		font-size: 16px;
		max-width: 1260px;
		margin: 0 auto;
	}

	@media (min-width: 768px) {
		.piggy-dashboard {
			padding: 40px;
		}
	}

	.piggy-dashboard {
		margin-left: auto;
		margin-right: auto;
		height: 100%;
		max-width: 1260px;
		padding: 16px;
	}
</style>
