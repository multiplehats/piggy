<script lang="ts">
	import { javascript } from '@codemirror/lang-javascript';
	import { __ } from '@wordpress/i18n';
	import { Loader2 } from 'lucide-svelte';
	import { onMount } from 'svelte';
	import type CodeMirror from './code-mirror.svelte';

	export let value = '';

	let codeMirror: typeof CodeMirror;
	let lazyLoaded = false;
	let lazyLoadError = false;

	const config = {
		parserOptions: {
			ecmaVersion: 2019
		},
		jQuery: true,
		env: {
			browser: true,
			node: false
		},
		rules: {
			'no-var': 'error',
			'no-console': 'warn',
			'no-implicit-globals': 'error'
		}
	};

	onMount(async () => {
		await import('./code-mirror.svelte')
			.then((module) => {
				codeMirror = module.default;
				lazyLoaded = true;
			})
			.catch((error) => {
				console.error(error);
				lazyLoadError = true;
				lazyLoaded = false;
			});
	});
</script>

{#if lazyLoaded}
	<svelte:component this={codeMirror} bind:value lang={javascript()} />
{:else if lazyLoadError}
	<div class="flex flex-col items-start justify-center h-full">
		<p class="text-left text-red-600 font-bold">
			{__(
				'There was an error loading the code editor. Please try again later or contact support.',
				'piggy'
			)}
		</p>
	</div>
{:else}
	<div class="inline-flex items-center">
		<Loader2 class="mr-2 animate-spin" />
		<span class="font-bold text-sm">Loading editor...</span>
	</div>
{/if}
