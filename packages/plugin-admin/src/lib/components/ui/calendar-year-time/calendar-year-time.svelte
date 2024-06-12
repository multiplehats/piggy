<script lang="ts">
	import {
		DateFormatter,
		getLocalTimeZone,
		parseTime,
		today,
		type DateValue
	} from '@internationalized/date';
	import { __ } from '@wordpress/i18n';
	import * as Calendar from '$lib/components/ui/calendar/index.js';
	import * as Select from '$lib/components/ui/select/index.js';
	import { cn } from '$lib/utils/tw';
	import { Calendar as CalendarPrimitive } from 'bits-ui';

	type $$Props = CalendarPrimitive.Props & {
		onChange?: (value: DateValue | DateValue[] | undefined) => void;
	};
	type $$Events = CalendarPrimitive.Events;

	export let value: $$Props['value'] = today(getLocalTimeZone());
	export let placeholder: $$Props['placeholder'] = today(getLocalTimeZone());
	export let weekdayFormat: $$Props['weekdayFormat'] = 'short';
	export let onChange: $$Props['onChange'] = undefined;

	const monthOptions = [
		__('January', 'piggy'),
		__('February', 'piggy'),
		__('March', 'piggy'),
		__('April', 'piggy'),
		__('May', 'piggy'),
		__('June', 'piggy'),
		__('July', 'piggy'),
		__('August', 'piggy'),
		__('September', 'piggy'),
		__('October', 'piggy'),
		__('November', 'piggy'),
		__('December', 'piggy')
	].map((month, i) => ({ value: i + 1, label: month }));

	const monthFmt = new DateFormatter(window.piggyMiddlewareConfig.siteLanguage, {
		month: 'short'
	});

	const yearOptions = Array.from({ length: 100 }, (_, i) => ({
		label: String(new Date().getFullYear() - i),
		value: new Date().getFullYear() - i
	}));

	const timeFormatter = new Intl.DateTimeFormat(window.piggyMiddlewareConfig.siteLanguage, {
		hour: 'numeric',
		minute: 'numeric',
		hour12: false
	});

	const timeOptions = Array.from({ length: 48 }, (_, i) => {
		const hours = Math.floor(i / 2);
		const minutes = i % 2 === 0 ? 0 : 30;
		const date = new Date();
		date.setHours(hours);
		date.setMinutes(minutes);
		return {
			value: `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`,
			label: timeFormatter.format(date)
		};
	});

	$: defaultYear = placeholder
		? {
				value: placeholder.year,
				label: String(placeholder.year)
		  }
		: undefined;

	$: defaultMonth = placeholder
		? {
				value: placeholder.month,
				label: monthFmt.format(placeholder.toDate(getLocalTimeZone()))
		  }
		: undefined;

	let className: $$Props['class'] = undefined;
	export { className as class };
</script>

<CalendarPrimitive.Root
	bind:value
	bind:placeholder
	onValueChange={(date) => onChange && onChange(date)}
	{weekdayFormat}
	class={cn('rounded-md border p-3', className)}
	{...$$restProps}
	on:keydown
	let:months
	let:weekdays
>
	<Calendar.Header>
		<Calendar.Heading class="flex w-full items-center justify-between gap-2">
			<Select.Root
				selected={defaultMonth}
				items={monthOptions}
				onSelectedChange={(v) => {
					if (!v || !placeholder) return;
					if (v.value === placeholder?.month) return;
					placeholder = placeholder.set({ month: v.value });
				}}
			>
				<Select.Trigger aria-label="Select month" class="w-[28%]">
					<Select.Value placeholder="Select month" />
				</Select.Trigger>
				<Select.Content sameWidth={false} class="max-h-[200px] min-w-[8rem] overflow-y-auto">
					{#each monthOptions as { value, label }}
						<Select.Item {value} {label}>
							{label}
						</Select.Item>
					{/each}
				</Select.Content>
			</Select.Root>
			<Select.Root
				selected={defaultYear}
				items={yearOptions}
				onSelectedChange={(v) => {
					if (!v || !placeholder) return;
					if (v.value === placeholder?.year) return;
					placeholder = placeholder.set({ year: v.value });
				}}
			>
				<Select.Trigger aria-label="Select year" class="w-[28%]">
					<Select.Value placeholder="Select year" />
				</Select.Trigger>
				<Select.Content sameWidth={false} class="max-h-[200px] min-w-[8rem] overflow-y-auto">
					{#each yearOptions as { value, label }}
						<Select.Item {value} {label}>
							{label}
						</Select.Item>
					{/each}
				</Select.Content>
			</Select.Root>
			<Select.Root
				items={timeOptions}
				onSelectedChange={(v) => {
					if (!v) return;
					if (!placeholder) return;

					const time = parseTime(v.value);

					placeholder = placeholder.set({
						hour: time.hour,
						minute: time.minute
					});
				}}
			>
				<Select.Trigger aria-label="Select time" class="w-[44%]">
					<Select.Value placeholder="Select time" />
				</Select.Trigger>
				<Select.Content sameWidth={false} class="max-h-[200px] min-w-[8rem] overflow-y-auto">
					{#each timeOptions as { value, label }}
						<Select.Item {value} {label}>
							{label}
						</Select.Item>
					{/each}
				</Select.Content>
			</Select.Root>
		</Calendar.Heading>
	</Calendar.Header>
	<Calendar.Months>
		{#each months as month}
			<Calendar.Grid>
				<Calendar.GridHead>
					<Calendar.GridRow class="flex">
						{#each weekdays as weekday}
							<Calendar.HeadCell>
								{weekday.slice(0, 2)}
							</Calendar.HeadCell>
						{/each}
					</Calendar.GridRow>
				</Calendar.GridHead>
				<Calendar.GridBody>
					{#each month.weeks as weekDates}
						<Calendar.GridRow class="mt-2 w-full">
							{#each weekDates as date}
								<Calendar.Cell {date}>
									<Calendar.Day {date} month={month.value} />
								</Calendar.Cell>
							{/each}
						</Calendar.GridRow>
					{/each}
				</Calendar.GridBody>
			</Calendar.Grid>
		{/each}
	</Calendar.Months>
</CalendarPrimitive.Root>
