<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import { Button } from '$lib/components/ui/button/index.js';

	export let disabled = false;
	export let saving = false;
</script>

<div class="flex items-center justify-end w-full mt-8 gap-4">
	<Button size="sm" variant="default" loading={saving} {disabled} type="submit">
		{__('Save', 'piggy')}
	</Button>
</div>
