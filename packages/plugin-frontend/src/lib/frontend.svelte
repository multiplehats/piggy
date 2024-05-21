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
			<div>Frontend thing</div>
		{/if}
	{/if}

	{#if isDev}
		<SvelteQueryDevtools buttonPosition="top-left" position="left" styleNonce="123" />
	{/if}
</QueryClientProvider>
