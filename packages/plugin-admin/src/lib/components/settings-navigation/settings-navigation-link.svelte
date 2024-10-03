<script lang="ts">
	import { Link } from "svelte-navigator";
	import type { LinkTypes } from "$lib/types";
	import { cn } from "$lib/utils/tw.js";

	export let href: string | undefined = undefined;
	export let target: string | undefined = undefined;
	export let active = false;
	export let type: LinkTypes = "link";

	$: classes = cn(
		"flex items-center overflow-hidden rounded-sm px-3 py-2 text-sm font-medium",
		active ? "text-secondary-fpregrpimd bg-secondary" : "text-gray-700"
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

<style>
	.active-link {
		background-color: #f7fafc;
		border-color: #cbd5e0;
	}
</style>
