<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import { SettingsLabel, type SettingsLabelProps } from '$lib/components/settings-label/index.js';
	import { Input, type FormInputEvent } from '$lib/components/ui/input';
	import { Label } from '$lib/components/ui/label';
	import * as Select from '$lib/components/ui/select';
	import { cn } from '$lib/utils/tw.js';
	import type { Selected } from 'bits-ui';

	type OnChangeFn<T> = (value: T) => void;

	type $$Props = SettingsLabelProps & {
		placeholder?: string | undefined;
		value?: Record<string, string> | undefined;
		class?: string | undefined;
	};

	let className: string | undefined = undefined;

	export let placeholder: $$Props['placeholder'] = undefined;
	export let value: $$Props['value'] = {};
	export let id: $$Props['id'];
	export { className as class };

	const languages = window.piggyMiddlewareConfig.languages;
	const currentLanguage = window.piggyMiddlewareConfig.currentLanguage;
	const items = languages.map((language) => ({
		value: language,
		label: language
	})) as Selected<string>[];

	let selected = items.find((item) => item.value === currentLanguage);
	let inputValue: string | undefined = undefined;

	const updateInputValue = () => {
		if (selected && value) {
			inputValue = value[selected.value];
		}
	};

	$: {
		updateInputValue();
	}

	function handleInputChange(event: FormInputEvent<FocusEvent | Event>) {
		const target = event?.target as HTMLInputElement;
		const inputVal = target.value as string;
		if (selected && value && inputVal) {
			value = {
				...value,
				[selected.value]: inputVal
			};
		}
	}

	function handleLanguageChange(e: Selected<string> | undefined) {
		if (e) {
			selected = e;
			updateInputValue();
		}
	}
</script>

<div class={cn('flex flex-col', className)}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<div class="flex relative max-w-xl mt-3 shadow-sm items-center justify-center">
		<Input
			{placeholder}
			on:blur={handleInputChange}
			on:change={handleInputChange}
			on:input={handleInputChange}
			{id}
			name={id}
			bind:value={inputValue}
			class={cn('rounded-r-none shadow-none border-r-0 h-8')}
		/>

		<Select.Root {selected} {items} onSelectedChange={handleLanguageChange}>
			<Select.Trigger class="w-[120px] rounded-l-none h-8 flex-shrink-0 z-10 border-l-0">
				<Select.Value placeholder={__('Select a language', 'piggy')} />
			</Select.Trigger>

			<Select.Content>
				<Select.Group>
					<Select.Label class="sr-only">{__('Languages', 'piggy')}</Select.Label>

					{#each languages as language}
						<Select.Item value={language} label={language}>{language}</Select.Item>
					{/each}
				</Select.Group>
			</Select.Content>

			<Select.Input name="selectedLanguage" />
		</Select.Root>
	</div>
</div>
