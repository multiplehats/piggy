<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import SettingsInput from '$lib/components/settings-input.svelte';
	import { setApiKeyMutationConfig } from '$lib/modules/piggy/mutations';
	import { getApiKeyQueryConfig } from '$lib/modules/piggy/queries';
	import type { AdminGetApiKeyResponse } from '$lib/modules/piggy/types';
	import ComboboxPiggyShop from '../combobox-piggy-shop.svelte';
	import SettingsSection from '../ui/settings-section/settings-section.svelte';

	const client = useQueryClient();
	const setApiKeyMutation = createMutation(setApiKeyMutationConfig(client));
	const query = createQuery<AdminGetApiKeyResponse>(getApiKeyQueryConfig());
</script>

<SettingsSection title={__('Connect to Piggy', 'piggy')}>
	<form class="grid grid-cols-2 gap-4">
		<SettingsInput
			id="api-key"
			label={__('API Key', 'piggy')}
			class="font-mono"
			value={$query.data?.api_key ?? undefined}
			on:change={(e) => {
				const value = e.currentTarget.value;

				$setApiKeyMutation.mutate({
					api_key: value
				});
			}}
		/>

		{#if $query.data?.api_key}
			<ComboboxPiggyShop />
		{/if}
	</form>
</SettingsSection>
