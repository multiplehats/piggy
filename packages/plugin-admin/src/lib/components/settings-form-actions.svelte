<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import { Button } from "$lib/components/ui/button/index.js";

	export let disabled = false;
	export let saving = false;
</script>

<div class="mt-8 flex w-full items-center justify-end gap-4">
	<Button size="sm" variant="default" loading={saving} {disabled} type="submit">
		{__("Save", "leat")}
	</Button>
</div>
