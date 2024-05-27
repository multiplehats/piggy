<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import { Label } from '$lib/components/ui/label';
	import * as Select from '$lib/components/ui/select';
	import { updateSettings } from '$lib/stores/settings';
	import { cn } from '$lib/utils/tw.js';

	interface Item {
		name: string;
		value: string;
	}

	export let items: Item[] = [];
	export let description: string | undefined = undefined;
	export let hideLabel = false;
	export let label: string;
	export let id: string;
	export let initialValue: string | undefined = undefined;

	function onSelectedChange(
		selectedOption: {
			value: string;
		} | null
	) {
		if (!selectedOption?.value) return selectedOption;

		return updateSettings({
			id: id,
			value: selectedOption.value as string
		});
	}

	$: selected = items.find((item) => item.value === initialValue);
</script>

<Label class={cn(hideLabel && 'sr-only')} for={id}>
	{label}
</Label>

{#if description}
	<p class="mb-2 text-sm">
		{description}
	</p>
{/if}

<div class={cn(!description && 'mt-2')}>
	<Select.Root {selected} {onSelectedChange} {...$$restProps}>
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
</div>
