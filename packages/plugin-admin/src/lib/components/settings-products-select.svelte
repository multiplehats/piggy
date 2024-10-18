<script lang="ts">
	import { createMutation, createQuery } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import { debounce } from "lodash-es";
	import Check from "lucide-svelte/icons/check";
	import ChevronsUpDown from "lucide-svelte/icons/chevrons-up-down";
	import { tick } from "svelte";
	import type { SettingsLabelProps } from "./settings-label";
	import SettingsLabel from "./settings-label/settings-label.svelte";
	import { Button } from "$lib/components/ui/button";
	import * as Command from "$lib/components/ui/command";
	import * as Popover from "$lib/components/ui/popover";
	import { LeatAdminService } from "$lib/modules/leat";
	import { MutationKeys, QueryKeys } from "$lib/utils/query-keys";
	import { cn } from "$lib/utils/tw.js";

	type $$Props = SettingsLabelProps & {
		id: string;
		label: string | undefined;
		description?: string | undefined;
		hideLabel?: boolean;
		multiple?: boolean;
		value: string[];
	};

	const service = new LeatAdminService();

	let className: string | undefined = undefined;
	let open = false;
	let searchTerm = "";

	export let multiple = true;
	export let id: string;
	export let value: string[] = [];
	export { className as class };

	function closeAndFocusTrigger(triggerId: string) {
		open = false;
		tick().then(() => {
			document.getElementById(triggerId)?.focus();
		});
	}

	const initialProductsQuery = createQuery({
		queryKey: [QueryKeys.wcProducts, id, value],
		queryFn: () => service.getInitialProducts(value),
		enabled: value.length > 0,
		refetchOnWindowFocus: false,
	});

	const searchProductsMutation = createMutation({
		mutationKey: [MutationKeys.searchProducts],
		retry: false,
		mutationFn: async (search: string) => service.searchProducts(search),
	});

	function searchProducts(e: Event) {
		searchTerm = (e.target as HTMLInputElement).value;
		$searchProductsMutation.mutate(searchTerm);
	}
	const searchProductsDebounced = debounce(searchProducts, 500);

	$: selectedProducts = $initialProductsQuery.data || [];

	$: displayedProducts = searchTerm ? $searchProductsMutation.data || [] : selectedProducts;

	$: selectedValue =
		selectedProducts.length > 0
			? selectedProducts.map((p) => p.title).join(", ")
			: __("Select product(s)...");

	function onSelectProduct(productId: string) {
		if (multiple) {
			value = value.includes(productId)
				? value.filter((id) => id !== productId)
				: [...value, productId];
		} else {
			value = value[0] === productId ? [] : [productId];
		}

		// Update selectedProducts immediately
		const selectedProduct = displayedProducts.find((p) => p.id.toString() === productId);
		if (selectedProduct) {
			if (multiple) {
				selectedProducts = value.includes(productId)
					? [...selectedProducts, selectedProduct]
					: selectedProducts.filter((p) => p.id.toString() !== productId);
			} else {
				selectedProducts = value.length > 0 ? [selectedProduct] : [];
			}
		}
	}
</script>

<div class={cn("flex flex-col justify-between", className)}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>
	<Popover.Root bind:open let:ids>
		<Popover.Trigger asChild let:builder>
			<Button
				builders={[builder]}
				variant="outline"
				role="combobox"
				aria-expanded={open}
				class="w-[300px] max-w-sm justify-between overflow-hidden text-ellipsis"
			>
				{selectedValue}
				<ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
			</Button>
		</Popover.Trigger>

		<Popover.Content class="w-[300px] p-0">
			<Command.Root onKeydown={searchProductsDebounced} shouldFilter={false}>
				<Command.Input placeholder={__("Search products...")} autocomplete="off" />

				<Command.Empty>{__("No products found")}</Command.Empty>
				<Command.Group>
					{#if displayedProducts.length > 0}
						{#each displayedProducts as product (product.id)}
							<Command.Item
								value={product.id.toString()}
								onSelect={() => {
									onSelectProduct(product.id.toString());
									if (!multiple) {
										closeAndFocusTrigger(ids.trigger);
									}
								}}
							>
								<Check
									class={cn(
										"mr-2 h-4 w-4",
										multiple
											? value?.includes(product.id.toString())
												? "opacity-100"
												: "opacity-0"
											: value[0] === product.id.toString()
												? "opacity-100"
												: "opacity-0"
									)}
								/>
								<!--  eslint-disable-next-line svelte/no-at-html-tags -->
								{@html product.title}
							</Command.Item>
						{/each}
					{/if}
				</Command.Group>
			</Command.Root>
		</Popover.Content>
	</Popover.Root>
</div>
