<script lang="ts">
	import { Label } from '$lib/components/ui/label';
	import { cn } from '$lib/utils/tw';
	import type { SettingsLabelProps } from '.';

	type $$Props = SettingsLabelProps;

	export let id: $$Props['id'];
	export let tooltip: $$Props['tooltip'];
	export let description: $$Props['description'];
	export let hideLabel: $$Props['hideLabel'];
	export let label: $$Props['label'];

	$: hideLabel = !!tooltip;
</script>

{#if label}
	<Label class={cn(hideLabel && 'sr-only')} for={id}>
		{label}
	</Label>
{/if}

{#if description}
	<p class="mt-0.5 text-xs">
		{description}
	</p>
{/if}
