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

	const initialCategoriesQuery = createQuery({
		queryKey: [QueryKeys.wcCategories, id, value],
		queryFn: () => service.getInitialCategories(value),
		enabled: value.length > 0,
		refetchOnWindowFocus: false,
	});

	const searchCategoriesMutation = createMutation({
		mutationKey: [MutationKeys.searchCategories],
		retry: false,
		mutationFn: async (search: string) => service.searchCategories(search),
	});

	function searchCategories(e: Event) {
		searchTerm = (e.target as HTMLInputElement).value;
		$searchCategoriesMutation.mutate(searchTerm);
	}
	const searchCategoriesDebounced = debounce(searchCategories, 500);

	$: selectedCategories = $initialCategoriesQuery.data || [];

	$: displayedCategories = searchTerm ? $searchCategoriesMutation.data || [] : selectedCategories;

	$: selectedValue =
		selectedCategories.length > 0
			? selectedCategories.map((c) => c.title).join(", ")
			: __("Select category(ies)...");

	function onSelectCategory(categoryId: string) {
		if (multiple) {
			value = value.includes(categoryId)
				? value.filter((id) => id !== categoryId)
				: [...value, categoryId];
		} else {
			value = value[0] === categoryId ? [] : [categoryId];
		}

		// Update selectedCategories immediately
		const selectedCategory = displayedCategories.find((c) => c.id.toString() === categoryId);
		if (selectedCategory) {
			if (multiple) {
				selectedCategories = value.includes(categoryId)
					? [...selectedCategories, selectedCategory]
					: selectedCategories.filter((c) => c.id.toString() !== categoryId);
			} else {
				selectedCategories = value.length > 0 ? [selectedCategory] : [];
			}
		}
	}
</script>

<div class={cn("flex flex-col justify-between", className)}>
	<SettingsLabel
		optional={$$props.optional}
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
			<Command.Root onKeydown={searchCategoriesDebounced} shouldFilter={false}>
				<Command.Input placeholder={__("Search categories...")} autocomplete="off" />

				<Command.Empty>{__("No categories found")}</Command.Empty>
				<Command.Group>
					{#if displayedCategories.length > 0}
						{#each displayedCategories as category (category.id)}
							<Command.Item
								value={category.id.toString()}
								onSelect={() => {
									onSelectCategory(category.id.toString());
									if (!multiple) {
										closeAndFocusTrigger(ids.trigger);
									}
								}}
							>
								<Check
									class={cn(
										"mr-2 h-4 w-4",
										multiple
											? value?.includes(category.id.toString())
												? "opacity-100"
												: "opacity-0"
											: value[0] === category.id.toString()
												? "opacity-100"
												: "opacity-0"
									)}
								/>
								<!--  eslint-disable-next-line svelte/no-at-html-tags -->
								{@html category.title}
							</Command.Item>
						{/each}
					{/if}
				</Command.Group>
			</Command.Root>
		</Popover.Content>
	</Popover.Root>
</div>
