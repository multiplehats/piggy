<script lang="ts">
	import type { InputEvents, InputProps } from ".";
	import { cn } from "$lib/utils/tw.js";

	type $$Props = InputProps;
	type $$Events = InputEvents;

	let className: $$Props["class"] = undefined;
	export let el: HTMLInputElement | undefined = undefined;
	export let value: $$Props["value"] = undefined;
	export { className as class };
</script>

<input
	class={cn(
		"!border-input !ring-offset-background !placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 min-h-9 w-full rounded-md !border bg-transparent px-3 py-2 text-sm !shadow-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50",
		className
	)}
	bind:this={el}
	bind:value
	on:blur
	on:change
	on:click
	on:focus
	on:keydown
	on:keypress
	on:keyup
	on:mouseover
	on:mouseenter
	on:mouseleave
	on:paste
	on:input
	{...$$restProps}
/>
