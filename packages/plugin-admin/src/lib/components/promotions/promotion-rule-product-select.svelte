<script lang="ts">
	import type { NotUndefined } from "@leat/types";
	import SettingsProductSelect from "$lib/components/settings-products-select.svelte";
	import type { GetPromotionRuleByIdResponse } from "$lib/modules/settings/types";

	export let selectedProducts: NotUndefined<GetPromotionRuleByIdResponse[0]["selectedProducts"]>;
</script>

<SettingsProductSelect multiple={false} {...selectedProducts} bind:value={selectedProducts.value} />
