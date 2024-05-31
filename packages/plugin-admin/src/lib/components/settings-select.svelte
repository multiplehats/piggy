<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import * as Select from '$lib/components/ui/select';
	import { cn } from '$lib/utils/tw.js';
	import SettingsFieldErrors from './settings-field-errors.svelte';
	import { SettingsLabel, type SettingsLabelProps } from './settings-label';

	interface Item {
		name: string;
		value: string;
	}

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		class: string | undefined;
		items: Item[];
		value?: string | undefined;
	};

	export { className as class };
	export let items: $$Props['items'] = [];
	export let id: $$Props['id'];
	export let value: $$Props['value'] = undefined;

	$: selected = items.find((item) => item.value === value);
</script>

<div class={className}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<Select.Root
		{selected}
		onSelectedChange={(selected) => {
			if (selected?.value) {
				value = selected.value;
			}
		}}
		{items}
	>
		<Select.Trigger class="max-w-xl">
			<Select.Value asChild>
				{#if selected}
					{selected.name}
				{:else}
					{__('Select an option', 'piggy')}
				{/if}
			</Select.Value>
		</Select.Trigger>

		<Select.Content>
			{#each items as item (item.value)}
				<Select.Item value={item.value}>{item.name}</Select.Item>
			{/each}
		</Select.Content>
	</Select.Root>

	<SettingsFieldErrors {...$$props} />
</div>
