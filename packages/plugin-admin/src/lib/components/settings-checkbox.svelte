<script lang="ts">
	import { Checkbox } from '$lib/components/ui/checkbox';
	import { cn } from '$lib/utils/tw';
	import type { CheckboxValue } from '@piggy/types/plugin/settings/adminTypes';
	import type { SettingsLabelProps } from './settings-label';
	import SettingsLabel from './settings-label/settings-label.svelte';

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		value: CheckboxValue;
		class: string | undefined;
	};

	export let id: string;
	export let value: $$Props['value'];
	export { className as class };

	$: checked = value === 'on';
</script>

<div class={className}>
	<SettingsLabel
		label={$$props.label}
		description={$$props.description}
		hideLabel={$$props.hideLabel}
		tooltip={$$props.tooltip}
		{id}
	/>

	<div class={cn('relative inline-flex mt-3 items-center w-full')}>
		<div class="flex items-center space-x-2">
			<Checkbox
				{checked}
				onCheckedChange={(boolean) => {
					value = boolean ? 'on' : 'off';
				}}
				{id}
			/>
		</div>
	</div>
</div>
