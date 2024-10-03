<script lang="ts">
	import { type DateValue, parseAbsoluteToLocal } from "@internationalized/date";
	import { __ } from "@wordpress/i18n";
	import CalendarIcon from "lucide-svelte/icons/calendar";
	import { onMount } from "svelte";
	import { SettingsLabel, type SettingsLabelProps } from "./settings-label";
	import CalendarYearTime from "./ui/calendar-year-time/calendar-year-time.svelte";
	import { cn } from "$lib/utils/tw.js";
	import * as Popover from "$lib/components/ui/popover/index.js";
	import { Button } from "$lib/components/ui/button/index.js";

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		placeholder?: string | undefined;
		value?: string | undefined;
		class?: string | undefined;
	};

	export { className as class };

	export let placeholder: $$Props["placeholder"] = undefined;
	export let value: $$Props["value"] = undefined;
	export let id: string;

	let rawValue: DateValue | undefined = undefined;

	onMount(() => {
		rawValue = value ? parseAbsoluteToLocal(value) : undefined;
	});
</script>

<div class={cn(className)}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<Popover.Root>
		<Popover.Trigger asChild let:builder>
			<Button
				variant="outline"
				class={cn(
					"w-[240px] justify-start text-left font-normal",
					!value && "text-muted-foreground"
				)}
				builders={[builder]}
			>
				<CalendarIcon class="mr-2 h-4 w-4" />
				{rawValue || __("Select date", "piggy")}
			</Button>
		</Popover.Trigger>
		<Popover.Content class="w-auto p-0" align="start">
			<CalendarYearTime
				onChange={(dates) => {
					rawValue = Array.isArray(dates) ? dates[0] : dates;
				}}
				placeholder={placeholder ? parseAbsoluteToLocal(placeholder) : undefined}
			/>
		</Popover.Content>
	</Popover.Root>
</div>
