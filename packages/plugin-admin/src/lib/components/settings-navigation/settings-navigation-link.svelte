<script lang="ts">
	import type { LinkTypes } from '$lib/types';
	import { cn } from '$lib/utils/tw.js';
	import { Link } from 'svelte-navigator';

	export let href: string | undefined = undefined;
	export let target: string | undefined = undefined;
	export let active = false;
	export let type: LinkTypes = 'link';

	$: classes = cn(
		'px-3 py-2 flex items-center rounded-sm text-sm font-medium overflow-hidden',
		active ? 'text-secondary-fpregrpimd bg-secondary' : 'text-gray-700'
	);
</script>

{#if type === 'link' && href}
	<Link to={href} class={classes}>
		<slot />
	</Link>
{:else}
	<a {href} {target} class={classes}>
		<slot />
	</a>
{/if}

<style>
	.active-link {
		background-color: #f7fafc;
		border-color: #cbd5e0;
	}
</style>
