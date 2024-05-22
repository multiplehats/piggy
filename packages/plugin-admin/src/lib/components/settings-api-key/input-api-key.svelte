<script lang="ts">
	import { createQuery } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import SettingsInput from '$lib/components/settings-input.svelte';
	import type { FormInputEvent } from '$lib/components/ui/input';
	import { getApiKeyQueryConfig } from '$lib/modules/piggy/queries';
	import type { AdminGetApiKeyResponse } from '$lib/modules/piggy/types';
	import ComboboxPiggyShop from '../combobox-piggy-shop.svelte';

	const handleOnChange = (e: FormInputEvent<Event>) => {
		e.preventDefault();

		const value = e.currentTarget.value;
	};

	const query = createQuery<AdminGetApiKeyResponse>(getApiKeyQueryConfig());
	$: console.log($query);
</script>

<SettingsInput
	id="api-key"
	label={__('API Key', 'piggy')}
	class="font-mono"
	value=""
	on:change={handleOnChange}
/>

{#if $query.data?.api_key}
	<ComboboxPiggyShop />
{/if}
