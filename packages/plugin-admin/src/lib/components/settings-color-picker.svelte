<script lang="ts">
	import Input from '$lib/components/settings-input.svelte';
	import { Label } from '$lib/components/ui/label';
	import { settingsState, updateSettings } from '$lib/stores/settings';
	import { colorToRgbaString } from '$lib/utils/color-converters';
	import { cn } from '$lib/utils/tw.js';
	import { debounce } from 'lodash-es';
	import { onMount } from 'svelte';
	import type { RgbaStringColorPicker } from 'vanilla-colorful/rgba-string-color-picker.js';
	import { clickOutsideAction } from '@piggy/lib';
	import type { PluginOptionsAdminKeys } from '@piggy/types/plugin';
	import 'vanilla-colorful/rgba-string-color-picker.js';

	export let label = 'Enter a label';
	export let id: string;
	export let value = '#1e88e5';

	let showPopup = false;

	let picker: RgbaStringColorPicker | null;
	let input: HTMLInputElement;

	onMount(() => {
		value = $settingsState[id as PluginOptionsAdminKeys].value as string;
	});

	const onPickerColorChanged = debounce((event: CustomEvent<{ value: string }>) => {
		const previousValue = value;
		value = event.detail.value;

		if (!id) {
			return;
		}

		input.value = value;

		updateSettings({
			id,
			value
		});
	}, 75);

	const onInputChange = debounce((event: Event) => {
		const previousValue = value;
		const _value = (event.target as HTMLInputElement).value;

		if (!id) {
			return;
		}

		value = colorToRgbaString(_value);

		if (picker) {
			picker.color = value;
		}

		updateSettings({
			id,
			value
		});
	}, 75);
</script>

<div class={cn('relative', $$props.class)}>
	<div
		class={cn('absolute z-20 left-0 top-16', showPopup ? 'block' : 'hidden')}
		use:clickOutsideAction={{ active: true, callback: () => (showPopup = false) }}
	>
		<rgba-string-color-picker color={value} on:color-changed={onPickerColorChanged} />
	</div>

	<div>
		<Label for={id}>{label}</Label>

		<div class="w-full mt-2 inline-flex rounded-lg">
			<div
				class="!p-1 bg-transparent border-input border !border-r-none rounded-l-sm !overflow-hidden"
			>
				<button
					on:click={() => (showPopup = !showPopup)}
					class="flex w-10 h-full overflow-hidden border rounded-md"
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
				class="!rounded-l-none !first:rounded-l-lg !last:rounded-r-lg w !border-l-0 !first:border-l !last:border-r focus:ring-none"
			/>
		</div>
	</div>
</div>
