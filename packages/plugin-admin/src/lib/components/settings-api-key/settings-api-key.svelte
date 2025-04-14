<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
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
	let showSavePrompt = false;
	let apiKeyError = "";
	let showApiKey = false;

	function validateApiKey(key: string) {
		const trimmedKey = key.trim();
		if (trimmedKey.length !== 53) {
			return __("API key must be exactly 53 characters long", "leat-crm");
		}
		return "";
	}

	$: {
		isLoading = $apiKeyQuery.isLoading || $shopQuery.isLoading;

		// Trim the API key and validate
		$settingsState.api_key.value = $settingsState.api_key.value.trim();
		apiKeyError = validateApiKey($settingsState.api_key.value);

		// Only show save prompt if the API key has changed and is different from initial value
		showSavePrompt = $settingsState.api_key.value !== initialApiKey;
	}

	function handleSaveApiKey() {
		if (apiKeyError) return;

		showSavePrompt = false;
		$saveSettingsMutation.mutate(settingsState, {
			onSuccess: () => {
				initialApiKey = $settingsState.api_key.value;
				$shopQuery.refetch();
			},
		});
	}

	function toggleApiKeyVisibility() {
		showApiKey = !showApiKey;
	}
</script>

<SettingsInput
	label={$settingsState.api_key.label}
	description={$settingsState.api_key.description}
	id={$settingsState.api_key.id}
	minlength={53}
	maxlength={53}
	type={showApiKey ? "text" : "password"}
	bind:value={$settingsState.api_key.value}
	inputWrapperClass="max-w-lg"
	autocomplete="off"
	data-1p-ignore
	data-lpignore="true"
>
	<button
		type="button"
		class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
		on:click={toggleApiKeyVisibility}
	>
		{#if showApiKey}
			{__("Hide", "leat-crm")}
		{:else}
			{__("Show", "leat-crm")}
		{/if}
	</button>
</SettingsInput>

{#if apiKeyError}
	<p class="mt-2 text-red-500">{apiKeyError}</p>
{/if}

{#if showSavePrompt}
	<div class="mb-4 mt-4">
		<p class="mb-2">{__("Save your API key to connect to a shop", "leat-crm")}</p>
		<button
			class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
			on:click={handleSaveApiKey}
			disabled={!!apiKeyError}
		>
			{__("Save API Key", "leat-crm")}
		</button>
	</div>
{/if}

{#if $apiKeyQuery.isSuccess}
	{#if $shopQuery.isLoading}
		<p>
			{__("Loading your shops...", "leat-crm")}
		</p>
	{:else if $shopQuery.isError}
		<p class="text-red-500">
			{__("There was an error loading your shops. Please try again later.", "leat-crm")}
			{$shopQuery.error.message}
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
