<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import { debounce } from "lodash-es";
	import { service } from "../../modules/leat";
	import SettingsCombobox from "../settings-combobox.svelte";
	import SettingsInput from "$lib/components/settings-input.svelte";
	import { saveSettingsMutationConfig } from "$lib/modules/settings/mutations";
	import { getSettingByIdQueryConfig } from "$lib/modules/settings/queries";
	import { settingsState } from "$lib/stores/settings";
	import { QueryKeys } from "$lib/utils/query-keys";

	export let isLoading = false;

	const client = useQueryClient();
	const apiKeyQuery = createQuery(getSettingByIdQueryConfig("api_key"));

	const shopQuery = createQuery({
		queryKey: [QueryKeys.leatShops],
		queryFn: async () => await service.getShops(),
		enabled: !!$settingsState.api_key.value,
		retry: false,
	});

	const saveSettingsMutation = createMutation(saveSettingsMutationConfig(client));

	let initialApiKey = $settingsState.api_key.value;

	const debouncedSaveSettings = debounce(() => {
		$saveSettingsMutation.mutate(settingsState, {
			onSuccess: () => {
				$shopQuery.refetch();
			},
		});
	}, 100);

	$: {
		isLoading = $apiKeyQuery.isLoading || $shopQuery.isLoading;

		if ($settingsState.api_key.value !== initialApiKey) {
			debouncedSaveSettings();
			initialApiKey = $settingsState.api_key.value;
			$shopQuery.refetch();
		}
	}
</script>

<SettingsInput
	label={$settingsState.api_key.label}
	description={$settingsState.api_key.description}
	id={$settingsState.api_key.id}
	bind:value={$settingsState.api_key.value}
/>

{#if $apiKeyQuery.isSuccess}
	{#if $shopQuery.isLoading}
		<p>
			{__("Loading your shops...", "leat")}
		</p>
	{:else if $shopQuery.isError}
		<p class="text-red-500">
			{#if $shopQuery.error.message.includes("Unauthenticated")}
				{__("Your API key is invalid. Please check your API key and try again.", "leat")}
			{:else}
				{__("There was an error loading your shops. Please try again later.", "leat")}
			{/if}
		</p>
	{:else if $shopQuery.isSuccess}
		<SettingsCombobox
			items={$shopQuery?.data
				? $shopQuery.data.map((shop) => ({
						label: shop.name,
						value: shop.uuid,
					}))
				: []}
			itemName="shop"
			label={$settingsState.shop_uuid.label}
			description={$settingsState.shop_uuid.description}
			id={$settingsState.shop_uuid.id}
			bind:value={$settingsState.shop_uuid.value}
		/>
	{/if}
{/if}
