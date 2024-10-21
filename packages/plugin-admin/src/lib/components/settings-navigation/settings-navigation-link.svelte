<script lang="ts">
	import { Link } from "svelte-navigator";
	import type { LinkTypes } from "$lib/types";
	import { cn } from "$lib/utils/tw.js";

	export let href: string | undefined = undefined;
	export let target: string | undefined = undefined;
	export let active = false;
	export let type: LinkTypes = "link";

	$: classes = cn(
		"flex items-center overflow-hidden  rounded-full px-3 py-2 text-sm font-medium hover:bg-white/15",
		active
			? "bg-white/20 text-white hover:text-white"
			: "bg-transparent text-white hover:bg-white/20 hover:text-white"
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
