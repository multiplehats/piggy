<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import { Button, type ButtonEventHandler } from "$lib/components/ui/button/index.js";
	import { useOnboarding } from "$lib/stores/onboarding";

	const onboarding = useOnboarding();

	export let disabled = false;
	export let saving = false;

	function handlePreviousStep(e: ButtonEventHandler<MouseEvent>) {
		e.preventDefault();

		onboarding.previousStep();
	}
</script>

<div class="mt-8 flex w-full items-center justify-end gap-4">
	<Button
		size="sm"
		variant="secondary"
		disabled={saving || disabled}
		on:click={handlePreviousStep}
	>
		{__("Back", "leat-crm")}
	</Button>

	<Button size="sm" variant="default" loading={saving} {disabled} type="submit">
		{__("Save and continue", "leat-crm")}
	</Button>
</div>
