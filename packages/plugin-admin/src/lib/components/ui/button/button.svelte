<script lang="ts">
	import { Button as ButtonPrimitive } from "bits-ui";
	import { type Events, type Props, buttonVariants } from ".";
	import { Pulse } from "$lib/components/ui/pulse";
	import { cn } from "$lib/utils/tw.js";

	type $$Props = Props;
	type $$Events = Events;

	let className: $$Props["class"] = undefined;
	export let variant: $$Props["variant"] = "default";
	export let size: $$Props["size"] = "default";
	export let builders: $$Props["builders"] = [];
	export { className as class };
	export let loading = false;
	export let icon: $$Props["icon"] = undefined;
	export let iconPlacement: $$Props["iconPlacement"] = "left";
	export let iconClasses: $$Props["iconClasses"] = undefined;
</script>

<ButtonPrimitive.Root
	{builders}
	class={cn(buttonVariants({ variant, size, className }))}
	type="button"
	disabled={loading}
	{...$$restProps}
	on:click
	on:keydown
>
	{#if icon && iconPlacement === "left"}
		<svelte:component
			this={icon}
			class={cn(
				"h-5 w-5",
				$$slots.default ? "mr-1" : "",
				size === "default" && "h-5 w-5",
				size === "sm" && "h-4 w-4",
				size === "lg" && "h-6 w-6",
				iconClasses
			)}
		/>
	{/if}

	<slot />

	{#if loading}
		<Pulse class="ml-2" />
	{/if}
</ButtonPrimitive.Root>
