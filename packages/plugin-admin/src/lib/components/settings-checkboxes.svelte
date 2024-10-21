<script lang="ts">
	import { Info } from "lucide-svelte";
	import type { CheckboxValue, CheckboxesOptions } from "@leat/types/plugin/settings/adminTypes";
	import SettingsFieldErrors from "./settings-field-errors.svelte";
	import {
		SettingsLabel,
		type SettingsLabelProps,
	} from "$lib/components/settings-label/index.js";
	import { Checkbox } from "$lib/components/ui/checkbox";
	import { Label } from "$lib/components/ui/label";
	import * as Tooltip from "$lib/components/ui/tooltip";
	import { cn } from "$lib/utils/tw";

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		options: CheckboxesOptions;
		value: Record<string, CheckboxValue>;
		class?: string | undefined;
	};

	export let id: $$Props["id"];
	export { className as class };
	export let options: $$Props["options"];
	export let value: $$Props["value"];
</script>

<div class={cn("w-full", className)} {id}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<div class="space-y-4">
		{#each Object.entries(options) as [optionId, { label, tooltip }], i}
			<div class="flex flex-row items-center">
				<div class="flex flex-row items-center space-x-2">
					<Checkbox
						id="{id}-{optionId}-{i}"
						checked={value[optionId] === "on"}
						onCheckedChange={(boolean) => {
							const newValue = boolean ? "on" : "off";

							value = {
								...value,
								[optionId]: newValue,
							};
						}}
					/>

					<Label for="{id}-{optionId}-{i}">
						{label}
					</Label>
				</div>

				{#if tooltip}
					<Tooltip.Root openDelay={100}>
						<Tooltip.Trigger>
							<Info class="ml-2 h-4 w-4" />
						</Tooltip.Trigger>

						<Tooltip.Content class="max-w-xs" side="right">
							{tooltip}
						</Tooltip.Content>
					</Tooltip.Root>
				{/if}
			</div>
		{/each}
	</div>

	<SettingsFieldErrors {...$$props} />
</div>
