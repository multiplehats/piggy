<script lang="ts">
	import { onMount } from "svelte";
	import { useLocation, useNavigate } from "svelte-navigator";
	import Navigation from "./settings-navigation/settings-navigation.svelte";
	import { cn } from "$lib/utils/tw.js";

	const navigate = useNavigate();

	const location = useLocation();

	$: isOnboarding = $location.pathname.startsWith("/onboarding");
	$: isMissingApiKey = !window.leatMiddlewareConfig.apiKeySet;

	onMount(() => {
		if (isMissingApiKey) {
			navigate("/onboarding");
		}
	});
</script>

<main class="layout-container">
	{#if !isOnboarding}
		<Navigation />
	{/if}

	<div
		class={cn(
			"app-container content-container relative",
			isOnboarding ? "mx-auto mt-0" : "mb-16 mt-8"
		)}
	>
		<slot />
	</div>
</main>

<style>
	.layout-container {
		min-height: 100vh;
	}

	.content-container {
		max-width: 1280px;
	}
</style>
