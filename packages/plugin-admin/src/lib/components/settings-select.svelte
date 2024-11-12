<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import type { SelectPropsWithoutHTML } from "bits-ui";
	import { SettingsLabel, type SettingsLabelProps } from "./settings-label";
	import * as Select from "$lib/components/ui/select";
	import { cn } from "$lib/utils/tw.js";
	// import SettingsFieldErrors from './settings-field-errors.svelte';

	interface Item {
		name: string;
		value: string;
	}

	let className: string | undefined = undefined;

	type SelectProps = SelectPropsWithoutHTML<string>;

	type $$Props = SettingsLabelProps & {
		class?: string | undefined;
		items: Item[];
		value?: string | undefined;
		hidden?: boolean | undefined;
		onSelectChange?: (selected: SelectProps["selected"]) => void | undefined;
	};

	export { className as class };
	export let items: $$Props["items"] = [];
	export let id: $$Props["id"];
	export let value: $$Props["value"] = undefined;
	export let hidden: $$Props["hidden"] = false;
	export let onSelectChange: $$Props["onSelectChange"] = undefined;

	function handleOnSelectChange(selected: SelectProps["selected"]) {
		if (selected?.value) {
			value = selected.value;
		}

		if (onSelectChange) {
			onSelectChange(selected);
		}
	}

	$: selected = items.find((item) => item.value === value);
</script>

<div class={cn(hidden && "hidden", className)}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<Select.Root {selected} onSelectedChange={handleOnSelectChange} {items}>
		<Select.Trigger class="max-w-xl">
			<Select.Value asChild>
				{#if selected}
					{selected.name}
				{:else}
					{__("Select an option", "leat-crm")}
				{/if}
			</Select.Value>
		</Select.Trigger>

		<Select.Content>
			{#each items as item (item.value)}
				<Select.Item value={item.value}>{item.name}</Select.Item>
			{/each}
		</Select.Content>
	</Select.Root>

	<!-- <SettingsFieldErrors {...$$props} /> -->
</div>
