<script>
	import { __ } from "@wordpress/i18n";
	import SettingsLogo from "./settings-logo.svelte";
	import { cn } from "$lib/utils/tw";

	export let title = __("Nothing here yet", "leat");
	export let description = __("Once you start adding rules, they will appear here.", "leat");
</script>

<div class="text-foreground/60 flex h-60 flex-col items-center justify-center gap-y-4 p-6 text-sm">
	<SettingsLogo class="h-10 w-10 opacity-25" />

	<div class="text-center">
		<h3 class="text-lg font-semibold">
			{title}
		</h3>

		<p class={cn("mt-2 max-w-[60ch] text-sm", $$slots.default && "mb-4")}>
			{description}
		</p>

		<slot></slot>
	</div>
</div>
