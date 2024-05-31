<script lang="ts">
	import { SettingsLabel, type SettingsLabelProps } from '$lib/components/settings-label/index.js';
	import { Input } from '$lib/components/ui/input';
	import { cn } from '$lib/utils/tw.js';

	type $$Props = SettingsLabelProps & {
		value: string | undefined;
		class: string | undefined;
	};

	let className: string | undefined = undefined;

	export let el: HTMLInputElement | undefined = undefined;
	export let value: $$Props['value'];
	export let placeholder: string | undefined = undefined;
	export let id: string;
	export { className as class };
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
		name={id}
		class="max-w-xl"
		{...$$restProps}
	/>
</div>
