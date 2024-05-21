<script lang="ts">
	import type { LinkTypes } from '$lib/types';
	import { cn } from '$lib/utils/tw.js';
	import { Link } from 'svelte-navigator';

	export let href: string | undefined = undefined;
	export let target: string | undefined = undefined;
	export let active = false;
	export let type: LinkTypes = 'link';

	$: classes = cn(
		'block rounded-md px-3 py-2 text-base font-medium text-secondary-foreground hover:bg-secondary hover:text-secondary-foreground',
		active && 'bg-secondary'
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
