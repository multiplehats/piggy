<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import type { Selected } from "bits-ui";
	import {
		SettingsLabel,
		type SettingsLabelProps,
	} from "$lib/components/settings-label/index.js";
	import { type FormInputEvent, Input } from "$lib/components/ui/input";
	import * as Select from "$lib/components/ui/select";
	import { cn } from "$lib/utils/tw.js";

	type $$Props = SettingsLabelProps & {
		placeholder?: string | undefined;
		value?: Record<string, string> | undefined | null;
		class?: string | undefined;
	};

	let className: string | undefined = undefined;

	export let placeholder: $$Props["placeholder"] = undefined;
	export let value: $$Props["value"] = {};
	export let id: $$Props["id"];
	export { className as class };

	// Ensure we have a valid value object
	if (!value || typeof value !== "object") {
		value = {};
	}

	// Get languages from config or fallback to at least one language
	const config = window.leatMiddlewareConfig || {};
	const languages =
		Array.isArray(config.languages) && config.languages.length > 0
			? config.languages
			: [config.currentLanguage || "en_US"];

	// Add any languages from existing values that might not be in the system anymore
	// but skip the 'default' key as it's not a real language
	if (value && typeof value === "object") {
		const valueLanguages = Object.keys(value).filter((lang) => lang !== "default");
		valueLanguages.forEach((lang) => {
			if (!languages.includes(lang)) {
				languages.push(lang);
			}
		});
	}

	const currentLanguage = config.currentLanguage || languages[0] || "en_US";

	const items = languages.map((language) => ({
		value: language,
		label: language,
	})) as Selected<string>[];

	let selected = items.find((item) => item.value === currentLanguage) || items[0];
	let inputValue: string | undefined = undefined;
	let isFocused = false;

	function updateInputValue() {
		if (selected && value) {
			if (Object.keys(value).length === 0) {
				// If no values are set, use an empty string
				inputValue = "";
			} else {
				// Use the selected language value, default key value as fallback, or the first available value
				inputValue =
					value[selected.value] || value.default || Object.values(value)[0] || "";
			}
		}
	}

	$: {
		updateInputValue();
	}

	function handleInputChange(event: FormInputEvent<FocusEvent | Event>) {
		const target = event?.target as HTMLInputElement;
		const inputVal = target.value as string;

		if (selected && inputVal !== undefined) {
			value = {
				...value,
				[selected.value]: inputVal,
			};

			// If this is the first value being set and no default exists,
			// update default and all other languages
			if (Object.keys(value).length === 1 || !value.default) {
				// Set default value for migration from old data
				value.default = inputVal;

				// Update all other languages
				for (const lang of languages) {
					if (lang !== selected.value && !value[lang]) {
						value[lang] = inputVal;
					}
				}
			}
		}
	}

	function handleLanguageChange(e: Selected<string> | undefined) {
		if (e) {
			selected = e;
			updateInputValue();
		}
	}
</script>

<div class={cn("flex flex-col", className)}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<div
		class={cn(
			"group relative flex max-w-xl items-center justify-center rounded-md shadow-sm",
			isFocused && "ring-ring ring-2 ring-offset-2"
		)}
	>
		<Input
			{placeholder}
			on:blur={handleInputChange}
			on:change={handleInputChange}
			on:input={handleInputChange}
			on:focus={() => (isFocused = true)}
			on:blur={() => (isFocused = false)}
			{id}
			name={id}
			bind:value={inputValue}
			class={cn(
				"h-8 rounded-r-none border-r-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
			)}
		/>

		<Select.Root
			{selected}
			{items}
			onSelectedChange={handleLanguageChange}
			onOpenChange={(isOpen) => (isFocused = isOpen)}
		>
			<Select.Trigger
				class="z-10 h-8 w-[120px] flex-shrink-0 rounded-l-none border-l-0 shadow-none focus:ring-0 focus:ring-offset-0"
			>
				<Select.Value placeholder={__("Select a language", "leat-crm")} />
			</Select.Trigger>

			<Select.Content>
				<Select.Group>
					<Select.Label class="sr-only">{__("Languages", "leat-crm")}</Select.Label>

					{#each languages as language}
						<Select.Item value={language} label={language}>{language}</Select.Item>
					{/each}
				</Select.Group>
			</Select.Content>

			<Select.Input name="selectedLanguage" />
		</Select.Root>
	</div>
</div>
