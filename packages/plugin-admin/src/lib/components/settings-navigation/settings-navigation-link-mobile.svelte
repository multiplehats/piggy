<script lang="ts">
	import { Link } from "svelte-navigator";
	import type { LinkTypes } from "$lib/types";
	import { cn } from "$lib/utils/tw.js";

	export let href: string | undefined = undefined;
	export let target: string | undefined = undefined;
	export let active = false;
	export let type: LinkTypes = "link";

	$: classes = cn(
		"text-secondary-foreground hover:bg-secondary hover:text-secondary-foreground block rounded-md px-3 py-2 text-base font-medium",
		active && "bg-secondary"
	);
</script>

{#if type === "link" && href}
	<Link to={href} class={classes}>
		<slot />
	</Link>
{:else}
	<a {href} {target} class={classes}>
		<slot />
	</a>
{/if}
