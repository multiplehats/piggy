<script lang="ts">
	import { __, sprintf } from '@wordpress/i18n';
	import { Button } from '$lib/components/ui/button/index.js';
	import * as Command from '$lib/components/ui/command/index.js';
	import * as Popover from '$lib/components/ui/popover/index.js';
	import { cn } from '$lib/utils/tw.js';
	import Check from 'lucide-svelte/icons/check';
	import ChevronsUpDown from 'lucide-svelte/icons/chevrons-up-down';
	import { tick } from 'svelte';
	import type { ComboboxProps } from '.';

	type $$Props = ComboboxProps;

	let className: $$Props['class'] = undefined;

	/** Needs to be a translate string, this turns into things such as "Select a `product`" */
	export let itemName: $$Props['itemName'];
	export let items: $$Props['items'] = [];
	export let widthClass: $$Props['widthClass'] = 'w-[300px] justify-between';
	export let id: $$Props['id'] = undefined;
	export { className as class };

	let open = false;
	let value = '';

	$: selectedValue =
		// translators: %s: itemName
		items.find((f) => f.value === value)?.label ?? sprintf(__('Select a %s'), itemName);

	// We want to refocus the trigger button when the user selects
	// an item from the list so users can continue navigating the
	// rest of the form with the keyboard.
	function closeAndFocusTrigger(triggerId: string) {
		open = false;
		tick().then(() => {
			document.getElementById(triggerId)?.focus();
		});
	}
</script>

<Popover.Root bind:open let:ids>
	<Popover.Trigger asChild let:builder>
		<Button
			builders={[builder]}
			variant="outline"
			role="combobox"
			aria-expanded={open}
			class={cn('h-8', className, widthClass)}
		>
			{selectedValue}
			<ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
		</Button>

		<input type="hidden" name={id} {value} {id} />
	</Popover.Trigger>
	<Popover.Content class={cn('p-0', widthClass)}>
		<Command.Root>
			<Command.Input
				placeholder={// translators: %s: label
				sprintf(__('Search a %s'), itemName)}
			/>

			<Command.Empty>
				{sprintf(
					// translators: %s: search term
					__('No results found for "%s"'),
					selectedValue
				)}
			</Command.Empty>

			<Command.Group>
				{#each items as item}
					<Command.Item
						value={item.value}
						onSelect={(currentValue) => {
							if (value === currentValue) {
								// Clear the value if the user selects the same item.
								value = '';
							} else {
								value = currentValue;
							}
							closeAndFocusTrigger(ids.trigger);
						}}
					>
						<Check class={cn('mr-2 h-4 w-4', value !== item.value && 'text-transparent')} />
						{item.label}
					</Command.Item>
				{/each}
			</Command.Group>
		</Command.Root>
	</Popover.Content>
</Popover.Root>
