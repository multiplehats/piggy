<script lang="ts">
	import type { SwitchValue } from "@leat/types/plugin/settings/adminTypes";
	import SettingsFieldErrors from "./settings-field-errors.svelte";
	import {
		SettingsLabel,
		type SettingsLabelProps,
	} from "$lib/components/settings-label/index.js";
	import { Switch } from "$lib/components/ui/switch";
	import { cn } from "$lib/utils/tw";

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		value: SwitchValue;
		class?: string | undefined;
		showErrors?: boolean;
	};

	export let id: string;
	export let value: $$Props["value"];
	export { className as class };
	export let showErrors = true;

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

	<div class={cn("relative inline-flex w-full items-center")}>
		<div class="flex items-center space-x-2">
			<Switch
				{checked}
				onCheckedChange={(boolean) => {
					value = boolean ? "on" : "off";
				}}
				{id}
			/>
		</div>
	</div>

	{#if showErrors}
		<SettingsFieldErrors {...$$props} />
	{/if}
</div>
