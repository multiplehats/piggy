<script lang="ts">
	import { MutationCache, QueryClient, QueryClientProvider } from "@tanstack/svelte-query";
	import { SvelteQueryDevtools } from "@tanstack/svelte-query-devtools";
	import { onMount } from "svelte";
	import type { IWooSettings, PluginOptionsFrontend } from "@piggy/types/plugin";
	import Dashboard from "./components/dashboard.svelte";
	import { isDev } from "$lib/config/environment";
	import {
		pluginSettings as settingsState,
		wcSettings as wcSettingsState,
	} from "$lib/modules/settings";
	import { WooCommerceStoreApiError } from "$lib/utils/errors.js";
	import { hooks } from "$lib/utils/hooks";
	import "./global.css";

	export let wcSettings: IWooSettings;
	export let pluginSettings: PluginOptionsFrontend;

	// Hydrate the state with the settings passed from the server.
	wcSettingsState.set(wcSettings);
	settingsState.set(pluginSettings);

	const mutationCache = new MutationCache({
		onError: (error) => {
			if (error instanceof WooCommerceStoreApiError) {
				const { message, status, statusText } = error;

				console.error(message, status, statusText);
			}
		},
	});

	onMount(async () => {
		hooks.doAction("on.init", $settingsState);
	});

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
	{#if $settingsState}
		{#if $settingsState.plugin_enable}
			<div class="piggy-dashboard">
				<Dashboard />
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
		border-radius: 6px;
		max-width: 1260px;
		margin: 0 auto;
	}

	@media (min-width: 768px) {
		.piggy-dashboard {
			padding: 16px;
		}
	}

	.piggy-dashboard {
		margin-left: auto;
		margin-right: auto;
		height: 100%;
		max-width: 1260px;
		padding: 80px 40px;
	}
</style>
