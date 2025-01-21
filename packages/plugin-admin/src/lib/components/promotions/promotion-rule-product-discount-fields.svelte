<script lang="ts">
	import type { NotUndefined } from "@leat/types";
	import SettingsInput from "$lib/components/settings-input.svelte";
	import SettingsSelect from "$lib/components/settings-select.svelte";
	import type { GetPromotionRuleByIdResponse } from "$lib/modules/settings/types";

	export let discountValue: NotUndefined<GetPromotionRuleByIdResponse[0]["discountValue"]>;
	export let discountType: NotUndefined<GetPromotionRuleByIdResponse[0]["discountType"]>;
</script>

<div class="grid grid-cols-2 gap-4">
	<SettingsSelect
		{...discountType}
		bind:value={discountType.value}
		items={Object.entries(discountType.options).map(([value, { label: name }]) => {
			return {
				value,
				name,
			};
		})}
		onSelectChange={(selected) => {
			if (selected) {
				discountValue.value = 0;
			}
		}}
	/>

	<SettingsInput
		{...discountValue}
		type="number"
		attributes={discountType.value === "percentage" ? { min: 0, max: 100 } : { min: 0 }}
		bind:value={discountValue.value}
	>
		<div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
			<span class="h-5 w-5 text-gray-600">
				{discountType.value === "percentage" ? "%" : ""}
			</span>
		</div>
	</SettingsInput>
</div>
