<script lang="ts">
	import { Label } from '$lib/components/ui/label';
	import { cn } from '$lib/utils/tw.js';
	import { Combobox, type ComboboxProps } from './ui/combobox';

	type $$Props = ComboboxProps & {
		id: string;
		label: string | undefined;
		description?: string | undefined;
		hideLabel?: boolean;
	};

	export let label: $$Props['label'] = undefined;
	export let hideLabel: $$Props['hideLabel'] = false;
	export let description: $$Props['description'] = undefined;
	export let widthClass: $$Props['widthClass'] = undefined;
	export let id: $$Props['id'];
	export let items: $$Props['items'];
	export let itemName: $$Props['itemName'];
</script>

<div class="flex flex-col justify-between">
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

	<Combobox
		{items}
		{itemName}
		{id}
		{widthClass}
		class={cn('max-w-xl', label && '!mt-2', $$props.class)}
	/>
</div>
