<script lang="ts">
	import type { PluginOptionsAdminKeys } from '@piggy/types/plugin';
	import { Badge } from '$lib/components/ui/badge';
	import { Label } from '$lib/components/ui/label';
	import { Switch } from '$lib/components/ui/switch';
	import * as Tooltip from '$lib/components/ui/tooltip';
	import { settingsState, updateSettings } from '$lib/stores/settings';
	import { Info } from 'lucide-svelte';

	export let id: string;
	export let tooltip = '';
	export let label = 'Enter a label';

	const onChange = () => {
		const value = checked ? 'on' : 'off';

		updateSettings({
			id,
			value
		});
	};

	$: checked = $settingsState[id as PluginOptionsAdminKeys].value === 'on';
</script>

<div class="relative inline-flex items-center">
	<div class="flex items-center space-x-2">
		<Switch
			{checked}
			onCheckedChange={(boolean) => {
				const value = boolean ? 'on' : 'off';

				updateSettings({
					id,
					value
				});
			}}
			{id}
		/>

		<Label for={id}>
			{label}
		</Label>
	</div>

	{#if tooltip}
		<Tooltip.Root>
			<Tooltip.Trigger>
				<Info class="w-4 h-4 ml-2" />
			</Tooltip.Trigger>

			<Tooltip.Content class="max-w-xs">
				{tooltip}
			</Tooltip.Content>
		</Tooltip.Root>
	{/if}
</div>
