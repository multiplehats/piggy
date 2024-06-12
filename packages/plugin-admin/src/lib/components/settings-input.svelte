<script lang="ts">
	import { Input, type InputProps } from '$lib/components/ui/input/index.js';
	import { cn } from '$lib/utils/tw.js';
	import { onMount } from 'svelte';
	import type { HTMLInputAttributes } from 'svelte/elements';
	import { SettingsLabel, type SettingsLabelProps } from './settings-label/index.js';
	import { Checkbox } from './ui/checkbox/index.js';

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps &
		InputProps & {
			/** Whether to show the input or not */
			withVisibility?: boolean;
			attributes?: HTMLInputAttributes | undefined;
			class?: string | undefined;
			el?: HTMLInputElement | undefined;
		};

	export { className as class };
	export let el: $$Props['el'] = undefined;
	export let placeholder: $$Props['placeholder'] = undefined;
	export let value: $$Props['value'] = undefined;
	export let attributes: $$Props['attributes'] = undefined;
	export let id: string;
	export let withVisibility: $$Props['withVisibility'] = false;

	let show = true;

	onMount(() => {
		if (withVisibility) {
			show = value !== null;
		}
	});
</script>

<div class={cn(className)}>
	<div class="flex flex-row items-center gap-3">
		{#if withVisibility}
			<Checkbox
				bind:checked={show}
				onCheckedChange={(val) => {
					if (Boolean(val) === false) {
						value = null;
					}
				}}
				class="mb-3"
			/>
		{/if}

		<SettingsLabel
			label={$$props.label}
			description={$$props.description}
			hideLabel={$$props.hideLabel}
			tooltip={$$props.tooltip}
			{id}
		/>
	</div>

	{#if show}
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
			class="max-w-xl"
			{...$$restProps}
			{...attributes}
		/>
	{/if}
</div>
