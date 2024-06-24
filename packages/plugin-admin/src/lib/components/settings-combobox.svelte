<script lang="ts">
	import { cn } from '$lib/utils/tw.js';
	import SettingsFieldErrors from './settings-field-errors.svelte';
	import type { SettingsLabelProps } from './settings-label';
	import SettingsLabel from './settings-label/settings-label.svelte';
	import { Combobox, type ComboboxProps } from './ui/combobox';

	type $$Props = ComboboxProps &
		SettingsLabelProps & {
			id: string;
			label: string | undefined;
			description?: string | undefined;
			hideLabel?: boolean;
		};
	let className: string | undefined = undefined;

	export { className as class };
	export let widthClass: $$Props['widthClass'] = undefined;
	export let id: $$Props['id'];
	export let items: $$Props['items'];
	export let itemName: $$Props['itemName'];
	export let value: $$Props['value'];
</script>

<div class={cn('flex flex-col justify-between', className)}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<Combobox {items} {itemName} {widthClass} bind:value class="max-w-xl" />

	<!-- Compatiblity for <form> -->
	<input type="hidden" name={id} bind:value {id} />

	<!-- <SettingsFieldErrors {...$$props} /> -->
</div>
