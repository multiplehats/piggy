<script lang="ts">
	import { createQuery } from "@tanstack/svelte-query";
	import PromotionRulesTable from "$lib/components/promotions/promotion-rules-table.svelte";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { QueryKeys } from "$lib/utils/query-keys";

	const service = new SettingsAdminService();

	const query = createQuery({
		queryKey: [QueryKeys.promotionRules],
		retry: false,
		queryFn: async () => await service.getPromotionRules(),
		refetchOnWindowFocus: true,
	});
</script>

{#if $query.isError}
	<p>Error: {$query.error.message}</p>
{:else if $query.isSuccess}
	<PromotionRulesTable promotions={$query.data} />
{/if}
