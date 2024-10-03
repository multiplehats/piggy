<script lang="ts">
	import { cn } from '$lib/utils/tw.js';
	import { onMount } from 'svelte';
	import { useLocation, useNavigate } from 'svelte-navigator';
	import Navigation from './settings-navigation/settings-navigation.svelte';

	const navigate = useNavigate();

	const location = useLocation();

	$: isOnboarding = $location.pathname.startsWith('/onboarding');
	$: isMissingApiKey = !window.piggyMiddlewareConfig.apiKeySet;

	onMount(() => {
		if (isMissingApiKey) {
			navigate('/onboarding');
		}
	});
</script>

<main class="layout-container">
	{#if !isOnboarding}
		<Navigation />
	{/if}

	<div
		class={cn(
			'app-container content-container relative',
			isOnboarding ? 'mt-0 mx-auto' : 'mt-8 mb-16'
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
