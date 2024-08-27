<script lang="ts">
	import { createMutation, createQuery } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { Button } from '$lib/components/ui/button';
	import * as Command from '$lib/components/ui/command';
	import * as Popover from '$lib/components/ui/popover';
	import { PiggyAdminService } from '$lib/modules/piggy';
	import { MutationKeys, QueryKeys } from '$lib/utils/query-keys';
	import { cn } from '$lib/utils/tw.js';
	import { debounce } from 'lodash-es';
	import Check from 'lucide-svelte/icons/check';
	import ChevronsUpDown from 'lucide-svelte/icons/chevrons-up-down';
	import { tick } from 'svelte';

	export let multiple = true;
	export let id: string;
	export let value: string | string[] | undefined = undefined;

	const service = new PiggyAdminService();

	let open = false;
	let searchTerm = '';

	function closeAndFocusTrigger(triggerId: string) {
		open = false;
		tick().then(() => {
			document.getElementById(triggerId)?.focus();
		});
	}

	const initialProductsQuery = createQuery({
		queryKey: [QueryKeys.wcProducts, id, value],
		queryFn: () => service.getInitialProducts(value),
		enabled: !!value,
		refetchOnWindowFocus: false
	});

	const searchProductsMutation = createMutation({
		mutationKey: [MutationKeys.searchProducts],
		retry: false,
		mutationFn: async (search: string) => service.searchProducts(search)
	});

	const searchProducts = (e: Event) => {
		searchTerm = (e.target as HTMLInputElement).value;
		$searchProductsMutation.mutate(searchTerm);
	};
	const searchProductsDebounced = debounce(searchProducts, 500);

	const onSelectProduct = (productId: string) => {
		if (multiple) {
			value = (value as string[])?.includes(productId)
				? (value as string[]).filter((id) => id !== productId)
				: [...((value as string[]) || []), productId];
		} else {
			value = value === productId ? undefined : productId;
		}

		// Update selectedProducts immediately
		const selectedProduct = displayedProducts.find((p) => p.id.toString() === productId);
		if (selectedProduct) {
			if (multiple) {
				selectedProducts = (value as string[])?.includes(productId)
					? [...selectedProducts, selectedProduct]
					: selectedProducts.filter((p) => p.id.toString() !== productId);
			} else {
				selectedProducts = value ? [selectedProduct] : [];
			}
		}
	};

	$: selectedProducts = $initialProductsQuery.data || [];

	$: displayedProducts = searchTerm ? $searchProductsMutation.data || [] : selectedProducts;

	$: selectedValue =
		selectedProducts.length > 0
			? selectedProducts.map((p) => p.title).join(', ')
			: __('Select product(s)...');
</script>

<Popover.Root bind:open let:ids>
	<Popover.Trigger asChild let:builder>
		<Button
			builders={[builder]}
			variant="outline"
			role="combobox"
			aria-expanded={open}
			class="w-[300px] justify-between max-w-sm text-ellipsis overflow-hidden"
		>
			{selectedValue}
			<ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
		</Button>
	</Popover.Trigger>

	<Popover.Content class="w-[300px] p-0">
		<Command.Root onKeydown={searchProductsDebounced} shouldFilter={false}>
			<Command.Input placeholder={__('Search products...')} autocomplete="off" />

			<Command.Empty>{__('No products found')}</Command.Empty>
			<Command.Group>
				{#if displayedProducts.length > 0}
					{#each displayedProducts as product (product.id)}
						<Command.Item
							value={product.id.toString()}
							onSelect={() => {
								console.log('onSelectProduct', product.id.toString());
								onSelectProduct(product.id.toString());
								if (!multiple) {
									closeAndFocusTrigger(ids.trigger);
								}
							}}
						>
							<Check
								class={cn(
									'mr-2 h-4 w-4',
									multiple
										? value?.includes(product.id.toString())
											? 'opacity-100'
											: 'opacity-0'
										: value === product.id.toString()
										? 'opacity-100'
										: 'opacity-0'
								)}
							/>

							{@html product.title}
						</Command.Item>
					{/each}
				{/if}
			</Command.Group>
		</Command.Root>
	</Popover.Content>
</Popover.Root>
