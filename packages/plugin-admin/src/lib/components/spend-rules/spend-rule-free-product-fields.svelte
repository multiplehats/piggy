<script lang="ts">
	import SettingsProductSelect from '$lib/components/settings-products-select.svelte';
	import type { GetSpendRuleByIdResponse } from '$lib/modules/settings/types';
	import type { NotUndefined } from '@piggy/types';

	export let selectedProducts: NotUndefined<GetSpendRuleByIdResponse[0]['selectedProducts']>;
</script>

<SettingsProductSelect multiple={false} {...selectedProducts} bind:value={selectedProducts.value} />
