<script lang="ts">
	import { createMutation, createQuery } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import { WalletMinimal } from "lucide-svelte";
	import { useNavigate } from "svelte-navigator";
	import TableEmptyState from "../table-empty-state.svelte";
	import { Badge } from "$lib/components/ui/badge";
	import { Button } from "$lib/components/ui/button";
	import * as Card from "$lib/components/ui/card/index.js";
	import * as Table from "$lib/components/ui/table/index.js";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { getStatusText } from "$lib/utils/status-text";

	const service = new SettingsAdminService();
	const navigate = useNavigate();

	const query = createQuery({
		queryKey: [QueryKeys.spendRules],
		retry: false,
		queryFn: async () => await service.getSpendRules(),
		refetchOnWindowFocus: true,
	});
	const mutateSync = createMutation({
		mutationFn: () => service.syncRewards(),
		mutationKey: ["spend-rules-sync"],
		onSuccess: () => {
			$query.refetch();
		},
	});
</script>

<div class="grid grid-cols-6 gap-6">
	<div class="col-span-6 sm:order-1 sm:col-span-1 sm:mt-2">
		<WalletMinimal class="text-foreground/75 mb-4 h-10 w-10" />

		<h2 class="mb-3 text-lg font-semibold">
			{__("Rewards")}
		</h2>

		<p>
			{__(
				"Sync rewards from Leat and manage how they are displayed on your website.",
				"leat"
			)}
		</p>
	</div>

	<Card.Root class="col-span-6 sm:order-2 sm:col-span-5">
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

				<Button
					size="sm"
					target="_blank"
					loading={$mutateSync.isPending}
					on:click={() => $mutateSync.mutate()}
				>
					{__("Sync rewards")}
				</Button>
			</div>
		</Card.Header>

		{#if $query?.data && $query.data.length > 0}
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
			>
				<Button
					size="xs"
					variant="secondary"
					target="_blank"
					loading={$mutateSync.isPending}
					on:click={() => $mutateSync.mutate()}
				>
					{__("Sync Rewards")}
				</Button>
			</TableEmptyState>
		{/if}
	</Card.Root>
</div>
