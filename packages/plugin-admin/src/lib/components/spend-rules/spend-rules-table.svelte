<script lang="ts">
	import { createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import { WalletMinimal } from "lucide-svelte";
	import { useNavigate } from "svelte-navigator";
	import TableEmptyState from "../table-empty-state.svelte";
	import SettingsSyncStatus from "../settings-sync-status.svelte";
	import { Badge } from "$lib/components/ui/badge";
	import { Button } from "$lib/components/ui/button";
	import * as Card from "$lib/components/ui/card/index.js";
	import * as Table from "$lib/components/ui/table/index.js";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { getStatusText } from "$lib/utils/status-text";

	const service = new SettingsAdminService();
	const navigate = useNavigate();
	const client = useQueryClient();

	const query = createQuery({
		queryKey: [QueryKeys.spendRules],
		retry: false,
		queryFn: async () => await service.getSpendRules(),
		refetchOnWindowFocus: true,
	});
</script>

<div class="grid grid-cols-8 gap-6">
	<div class="col-span-8 sm:order-1 sm:col-span-2 sm:mt-2">
		<WalletMinimal class="text-foreground/75 mb-4 h-10 w-10" />

		<h2 class="mb-3 text-lg font-semibold">
			{__("Rewards")}
		</h2>

		<p class="mb-2">
			{__("Create and manage rewards to allow customers to spend their credits.")}
		</p>

		<p class="text-muted-foreground/75 mb-4 text-xs">
			{__(
				"Any rewards removed from Leat will be automatically synchronized and removed from your WordPress site upon next sync."
			)}
		</p>

		<SettingsSyncStatus
			key="rewards"
			title={__("Sync rewards", "leat")}
			mutationFn={() => service.syncRewards()}
			onMutationSuccess={() => client.invalidateQueries({ queryKey: [QueryKeys.spendRules] })}
		/>
	</div>

	<Card.Root class="col-span-8 sm:order-2 sm:col-span-6">
		<Card.Header class="flex  items-center justify-between sm:flex-row">
			<div class="grid gap-2">
				<Card.Title>{__("Rewards overview")}</Card.Title>
			</div>

			<div class="flex items-center justify-between gap-2">
				<Button
					size="sm"
					variant="secondary"
					href="https://business.leat.com/loyalty/rewards?type=all"
					target="_blank"
					rel="noopener noreferrer"
				>
					{__("Add reward")}
				</Button>
			</div>
		</Card.Header>

		{#if $query?.data && $query.data.length > 0}
			<Card.Content>
				<Table.Root>
					<Table.Header>
						<Table.Row>
							<Table.Head>{__("Title", "leat-crm")}</Table.Head>
							<Table.Head>{__("Created at", "leat-crm")}</Table.Head>
							<Table.Head class="text-right">{__("Status", "leat-crm")}</Table.Head>
						</Table.Row>
					</Table.Header>
					<Table.Body>
						{#each $query.data as rule}
							<Table.Row
								class="cursor-pointer"
								on:click={() => navigate(`spend-rules/${rule.id}`)}
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
				description={__("Sync your rewards to see them here.", "leat")}
			></TableEmptyState>
		{/if}
	</Card.Root>
</div>
