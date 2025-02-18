<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import { Button } from "$lib/components/ui/button/index.js";

	export let disabled = false;
	export let saving = false;
</script>

<div
	class="bg-background/95 supports-[backdrop-filter]:bg-background/60 sticky bottom-8 z-20 mx-auto w-full max-w-fit rounded-lg border p-2 shadow-lg backdrop-blur"
>
	<div class="flex items-center justify-between gap-4">
		<Button size="sm" variant="default" loading={saving} {disabled} type="submit">
			{__("Save settings", "leat-crm")}
		</Button>

		<!-- Go to business.leat-->
		<Button size="sm" variant="link" href="https://business.leat.com" target="_blank">
			{__("Go to Leat dashboard", "leat-crm")}
		</Button>
	</div>
</div>
