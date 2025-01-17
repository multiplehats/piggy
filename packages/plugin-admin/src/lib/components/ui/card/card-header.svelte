<script lang="ts">
	import type { HTMLAttributes } from "svelte/elements";
	import { cn } from "$lib/utils/tw.js";

	type $$Props = HTMLAttributes<HTMLDivElement>;

	let className: $$Props["class"] = undefined;
	export { className as class };
</script>

<div class={cn("flex flex-col space-y-1.5 border-b px-4 py-3", className)} {...$$restProps}>
	<slot />
</div>
