<script lang="ts">
	import type { SettingsLabelProps } from ".";
	import { Label } from "$lib/components/ui/label";
	import { cn } from "$lib/utils/tw";

	type $$Props = SettingsLabelProps;

	let className: string | undefined = undefined;

	export let id: $$Props["id"];
	export let tooltip: $$Props["tooltip"] = undefined;
	export let description: $$Props["description"] = undefined;
	export let hideLabel: $$Props["hideLabel"] = false;
	export let label: $$Props["label"];
	export let optional: $$Props["optional"] = false;
	export { className as class };

	$: hideLabel = !!tooltip;
</script>

<div class={cn("mb-3", className)}>
	{#if label}
		<Label class={cn(hideLabel && "sr-only")} for={id}>
			{label}

			{#if !optional}
				<span>*</span>
			{/if}
		</Label>
	{/if}

	{#if description}
		<p class="mt-0.5 text-xs">
			{description}
		</p>
	{/if}
</div>
