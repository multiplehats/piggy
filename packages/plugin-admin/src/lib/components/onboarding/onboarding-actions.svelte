<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import { Button, type ButtonEventHandler } from '$lib/components/ui/button/index.js';
	import { returnToPreviousStep } from '$lib/stores/onboarding';

	function handlePreviousStep(e: ButtonEventHandler<MouseEvent>) {
		e.preventDefault();

		returnToPreviousStep();
	}
</script>

<div class="flex items-center justify-end w-full mt-8 gap-4">
	<Button size="sm" variant="secondary" on:click={handlePreviousStep}>{__('Back', 'piggy')}</Button>
	<Button size="sm" variant="default" type="submit">{__('Save and continue', 'piggy')}</Button>
</div>
