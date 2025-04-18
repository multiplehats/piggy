<script lang="ts">
	import { useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import { BadgePercent } from "lucide-svelte";
	import { useNavigate } from "svelte-navigator";
	import TableEmptyState from "../table-empty-state.svelte";
	import SettingsSyncStatus from "../settings-sync-status.svelte";
	import { Badge } from "$lib/components/ui/badge";
	import { Button } from "$lib/components/ui/button";
	import * as Card from "$lib/components/ui/card/index.js";
	import * as Table from "$lib/components/ui/table/index.js";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { getStatusText } from "$lib/utils/status-text";
	import type { GetPromotionRulesResponse } from "$lib/modules/settings/types";
	import { QueryKeys } from "$lib/utils/query-keys";

	export let promotions: GetPromotionRulesResponse = [];

	const client = useQueryClient();
	const service = new SettingsAdminService();
	const navigate = useNavigate();
</script>

<div class="grid grid-cols-8 gap-6">
	<div class="col-span-8 sm:order-1 sm:col-span-2 sm:mt-2">
		<BadgePercent class="text-foreground/75 mb-4 h-10 w-10" />

		<h2 class="mb-3 text-lg font-semibold">
			{__("Promotions")}
		</h2>

		<p class="mb-2">
			{__("Sync promotionss from Leat and manage how they are displayed on your website.")}
		</p>

		<p class="text-muted-foreground/75 mb-4 text-xs">
			{__(
				"Any promotions removed from Leat will be automatically synchronized and removed from your WordPress site upon next sync."
			)}
		</p>

		<SettingsSyncStatus
			key="promotions"
			title={__("Sync promotions", "leat")}
			mutationFn={() => service.syncPromotions()}
			onMutationSuccess={() =>
				client.invalidateQueries({ queryKey: [QueryKeys.promotionRules] })}
		/>
	</div>

	<Card.Root class="col-span-8 sm:order-2 sm:col-span-6">
		<Card.Header class="flex  items-center justify-between sm:flex-row">
			<div class="grid gap-2">
				<Card.Title>{__("Promotion overview")}</Card.Title>
			</div>

			<div class="flex items-center justify-between gap-2">
				<Button
					size="sm"
					variant="secondary"
					href="https://business.leat.com/loyalty/promotion-engine/promotions"
					target="_blank"
					rel="noopener noreferrer"
				>
					{__("Add promotion")}
				</Button>
			</div>
		</Card.Header>

		{#if promotions && promotions.length > 0}
			<Card.Content>
				<Table.Root>
					<Table.Header>
						<Table.Row>
							<Table.Head>{__("Title", "leat")}</Table.Head>
							<Table.Head>{__("Created at", "leat")}</Table.Head>
							<Table.Head class="text-right">{__("Status", "leat")}</Table.Head>
						</Table.Row>
					</Table.Header>

					<Table.Body>
						{#each promotions as rule}
							<Table.Row
								class="cursor-pointer"
								on:click={() => navigate(`promotion-rules/${rule.id}`)}
							>
								<Table.Cell>
									<!--  eslint-disable-next-line svelte/no-at-html-tags -->
									<div class="font-medium">{@html rule.title.value}</div>
								</Table.Cell>

								<Table.Cell>
									{new Date(rule.createdAt).toLocaleDateString(undefined, {
										year: "numeric",
										month: "long",
										day: "numeric",
									})}
								</Table.Cell>

								<Table.Cell class="text-right">
									<Badge
										variant={rule.status.value === "publish"
											? "default"
											: "secondary"}
									>
										{getStatusText(rule.status.value)}
									</Badge>
								</Table.Cell>
							</Table.Row>
						{/each}
					</Table.Body>
				</Table.Root>
			</Card.Content>
		{:else}
			<TableEmptyState
				title={__("Nothing here yet", "leat")}
				description={__("Sync your promotions to see them here.", "leat")}
			></TableEmptyState>
		{/if}
	</Card.Root>
</div>
