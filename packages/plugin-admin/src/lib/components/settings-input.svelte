<script lang="ts">
	import { Input } from '$lib/components/ui/input';
	import { Label } from '$lib/components/ui/label';
	import { cn } from '$lib/utils/tw.js';

	let className: string | undefined = undefined;
	export let el: HTMLInputElement | undefined = undefined;
	export let label: string | undefined = undefined;
	export let hideLabel = false;
	export let placeholder: string | undefined = undefined;
	export let description: string | undefined = undefined;
	export let value: string | number | undefined = undefined;
	export let id: string;
	export { className as class };
</script>

<div>
	{#if label}
		<Label class={cn(hideLabel && 'sr-only')} for={id}>
			{label}
		</Label>
	{/if}

	{#if description}
		<p class="mb-2 mt-0.5 text-sm">
			{description}
		</p>
	{/if}

	<Input
		{placeholder}
		bind:el
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
		{id}
		name={id}
		class={cn('max-w-xl', label && '!mt-2', className)}
		{...$$restProps}
	/>
</div>
