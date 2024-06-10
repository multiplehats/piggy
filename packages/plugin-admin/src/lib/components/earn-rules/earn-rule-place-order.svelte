<script lang="ts">
	import SettingsTranslateableInput from '../settings-translateable-input.svelte';

	export let label: Record<string, string> | null;
</script>

<div class="">
	Variable amount of points to earn by customers when they place an order
	<SettingsTranslateableInput />
</div>
