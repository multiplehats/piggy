<script lang="ts">
	import { Input, type InputProps } from '$lib/components/ui/input/index.js';
	import { cn } from '$lib/utils/tw.js';
	import { SettingsLabel, type SettingsLabelProps } from './settings-label';

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps &
		InputProps & {
			class?: string | undefined;
			el?: HTMLInputElement | undefined;
		};

	export { className as class };
	export let el: $$Props['el'] = undefined;
	export let placeholder: $$Props['placeholder'] = undefined;
	export let value: $$Props['value'] = undefined;
	export let id: string;
</script>

<div class={cn(className)}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

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
	/>
</div>
