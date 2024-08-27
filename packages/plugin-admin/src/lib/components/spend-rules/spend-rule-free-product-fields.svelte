<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { Button } from '$lib/components/ui/button';
	import * as Command from '$lib/components/ui/command';
	import * as Popover from '$lib/components/ui/popover';
	import { PiggyAdminService } from '$lib/modules/piggy';
	import { QueryKeys } from '$lib/utils/query-keys';
	import { tick } from 'svelte';
	import SettingsCombobox from '../settings-combobox.svelte';

	const service = new PiggyAdminService();

	let open = false;
	let product = '';
	let productOptions = [];

	// We want to refocus the trigger button when the user selects
	// an item from the list so users can continue navigating the
	// rest of the form with the keyboard.
	function closeAndFocusTrigger(triggerId: string) {
		open = false;
		tick().then(() => {
			document.getElementById(triggerId)?.focus();
		});
	}

	// TODO: Fetch products
	const query = createQuery({
		queryKey: [QueryKeys.wcProducts],
		retry: false,
		queryFn: async () => service.searchProducts('t-shirt'),
		refetchOnWindowFocus: true
	});

	$: console.log($query.data);

	const searchProduct = (e: KeyboardEvent) => {
		const search = (e.target as HTMLInputElement).value;

		console.log(search);
	};

	const onSelectProduct = (product: string) => {
		console.log(product);
	};
</script>

<div class="grid">
	<Popover.Root bind:open let:ids>
		<Popover.Trigger asChild let:builder>
			<Button
				builders={[builder]}
				variant="outline"
				role="combobox"
				aria-expanded={open}
				class="w-[300px] justify-between overflow-hidden"
			>
				{product || 'Search Product...'}
			</Button>
		</Popover.Trigger>
		<Popover.Content class="w-[300px] p-0">
			<Command.Root onKeydown={searchProduct}>
				<Command.Input placeholder="Search Product..." autocomplete="off" />
				<Command.Group>
					{#each productOptions as option (option.value)}
						<Command.Item
							value={option.value}
							onSelect={() => {
								onSelectProduct(option.value);
								closeAndFocusTrigger(ids.trigger);
							}}
						>
							{option.label}
						</Command.Item>
					{/each}
				</Command.Group>
			</Command.Root>
		</Popover.Content>
	</Popover.Root>
	<!-- <SettingsCombobox
		items={$query?.data
			? $query.data.map((reward) => ({
					label: product.title,
					value: product.id
			  }))
			: []}
		itemName={__('Products', 'piggy')}
	/> -->
</div>
