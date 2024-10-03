<script lang="ts">
	import type { CheckboxValue } from "@piggy/types/plugin/settings/adminTypes";
	import type { SettingsLabelProps } from "./settings-label";
	import SettingsLabel from "./settings-label/settings-label.svelte";
	import { cn } from "$lib/utils/tw";
	import { Checkbox } from "$lib/components/ui/checkbox";

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		value: CheckboxValue;
		class: string | undefined;
	};

	export let id: string;
	export let value: $$Props["value"];
	export { className as class };

	$: checked = value === "on";
</script>

<div class={className}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<div class={cn("relative mt-3 inline-flex w-full items-center")}>
		<div class="flex items-center space-x-2">
			<Checkbox
				{checked}
				onCheckedChange={(boolean) => {
					value = boolean ? "on" : "off";
				}}
				{id}
			/>
		</div>
	</div>
</div>
