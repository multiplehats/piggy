<script lang="ts">
	import { DateFormatter, getLocalTimeZone, today, type DateValue } from '@internationalized/date';
	import { Button } from '$lib/components/ui/button/index.js';
	import { Calendar } from '$lib/components/ui/calendar/index.js';
	import { Input, type InputProps } from '$lib/components/ui/input/index.js';
	import * as Popover from '$lib/components/ui/popover/index.js';
	import { cn } from '$lib/utils/tw.js';
	import CalendarIcon from 'lucide-svelte/icons/calendar';
	import { SettingsLabel, type SettingsLabelProps } from './settings-label';
	import CalendarYearTime from './ui/calendar-year-time/calendar-year-time.svelte';

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		value?: DateValue | undefined;
		class?: string | undefined;
	};

	export { className as class };

	export let value: $$Props['value'] = undefined;
	export let id: string;

	const df = new DateFormatter('en-US', {
		dateStyle: 'long'
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
					'w-[240px] justify-start text-left font-normal',
					!value && 'text-muted-foreground'
				)}
				builders={[builder]}
			>
				<CalendarIcon class="mr-2 h-4 w-4" />
				{value ? df.format(value.toDate(getLocalTimeZone())) : 'Pick a date'}
			</Button>
		</Popover.Trigger>
		<Popover.Content class="w-auto p-0" align="start">
			<!-- <Calendar bind:value /> -->
			{value?.toDate(getLocalTimeZone()).toISOString()}
			<CalendarYearTime bind:value />
		</Popover.Content>
	</Popover.Root>
</div>
