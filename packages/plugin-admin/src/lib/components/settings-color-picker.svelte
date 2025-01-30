<script lang="ts">
	import { debounce } from "lodash-es";
	import { onMount } from "svelte";
	import type { RgbaStringColorPicker } from "vanilla-colorful/rgba-string-color-picker.js";
	import { clickOutsideAction } from "@leat/lib";
	import type { PluginOptionsAdminKeys } from "@leat/types/plugin";
	import Input from "$lib/components/settings-input.svelte";
	import { Label } from "$lib/components/ui/label";
	import { settingsState } from "$lib/stores/settings";
	import { colorToRgbaString } from "$lib/utils/color-converters";
	import { cn } from "$lib/utils/tw.js";
	import "vanilla-colorful/rgba-string-color-picker.js";

	export let label = "Enter a label";
	export let id: string;
	export let value = "#1e88e5";

	let showPopup = false;

	let picker: RgbaStringColorPicker | null = null;
	let input: HTMLInputElement;

	onMount(() => {
		value = $settingsState[id as PluginOptionsAdminKeys].value as string;
	});

	const onPickerColorChanged = debounce((event: CustomEvent<{ value: string }>) => {
		value = event.detail.value;

		if (!id) {
			return;
		}

		input.value = value;
	}, 75);

	const onInputChange = debounce((event: Event) => {
		const _value = (event.target as HTMLInputElement).value;

		if (!id) {
			return;
		}

		value = colorToRgbaString(_value);

		if (picker) {
			// @ts-expect-error -- This exists but is not typed
			picker.color = value;
		}
	}, 75);
</script>

<div class={cn("relative", $$props.class)}>
	<div
		class={cn("absolute left-0 top-16 z-20", showPopup ? "block" : "hidden")}
		use:clickOutsideAction={{ active: true, callback: () => (showPopup = false) }}
	>
		<rgba-string-color-picker color={value} on:color-changed={onPickerColorChanged} />
	</div>

	<div>
		<Label for={id}>{label}</Label>

		<div class="mt-2 inline-flex w-full rounded-lg">
			<div
				class="border-input !border-r-none !overflow-hidden rounded-l-sm border bg-transparent !p-1"
			>
				<button
					on:click={() => (showPopup = !showPopup)}
					class="flex h-full w-10 overflow-hidden rounded-md border"
					style:background-color={value}
				>
					<span class="sr-only">Open color picker</span>
				</button>
			</div>

			<Input
				{label}
				{id}
				bind:el={input}
				on:click={() => (showPopup = !showPopup)}
				on:keyup={() => (showPopup = !showPopup)}
				on:input={onInputChange}
				bind:value
				type="text"
				placeholder="rgba(0,0,0)"
				class="!first:rounded-l-lg !last:rounded-r-lg w !first:border-l !last:border-r focus:ring-none !rounded-l-none !border-l-0"
			/>
		</div>
	</div>
</div>
