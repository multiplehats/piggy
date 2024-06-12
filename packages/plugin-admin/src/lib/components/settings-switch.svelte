<script lang="ts">
	import { SettingsLabel, type SettingsLabelProps } from '$lib/components/settings-label/index.js';
	import { Switch } from '$lib/components/ui/switch';
	import { cn } from '$lib/utils/tw';
	import type { SwitchValue } from '@piggy/types/plugin/settings/adminTypes';
	import SettingsFieldErrors from './settings-field-errors.svelte';

	let className: string | undefined = undefined;

	type $$Props = SettingsLabelProps & {
		value: SwitchValue;
		class?: string | undefined;
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

	<div class={cn('relative inline-flex items-center w-full')}>
		<div class="flex items-center space-x-2">
			<Switch
				{checked}
				onCheckedChange={(boolean) => {
					value = boolean ? 'on' : 'off';
				}}
				{id}
			/>
		</div>
	</div>

	<SettingsFieldErrors {...$$props} />
</div>
