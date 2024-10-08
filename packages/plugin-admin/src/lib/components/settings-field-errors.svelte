<script lang="ts">
	import { zPluginOptionsAdmin } from "@piggy/types";
	import type { PluginOptionsAdminKeys } from "@piggy/types";
	import { cn } from "$lib/utils/tw";

	let className: string | undefined = undefined;

	// BIt hacky of a component, but whatever it works for now.

	$: result = zPluginOptionsAdmin.shape[$$props.id as PluginOptionsAdminKeys].safeParse({
		...$$props,
	});
	$: errors = result.success ? null : result.error.format();
</script>

{#if errors?._errors?.length}
	<div class={cn("mt-2 text-sm text-red-500", className)}>
		{#each errors._errors as error}
			<p>{error}</p>
		{/each}
	</div>
{/if}
