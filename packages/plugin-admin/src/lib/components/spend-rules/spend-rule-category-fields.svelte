<script lang="ts">
	import type { NotUndefined } from "@leat/types";
	import SettingsCategorySelect from "$lib/components/settings-categories-select.svelte";
	import type { GetSpendRuleByIdResponse } from "$lib/modules/settings/types";

	export let selectedCategories: NotUndefined<GetSpendRuleByIdResponse[0]["selectedCategories"]>;
</script>

<SettingsCategorySelect
	multiple={true}
	{...selectedCategories}
	bind:value={selectedCategories.value}
/>
