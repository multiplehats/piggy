<script lang="ts">
	import { createMutation, createQuery } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import { WalletMinimal } from "lucide-svelte";
	import { useNavigate } from "svelte-navigator";
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

{#if $query?.data}
	<div class="grid grid-cols-6 gap-6">
		<div class="col-span-6 sm:order-1 sm:col-span-1 sm:mt-2">
			<WalletMinimal class="text-foreground/75 mb-4 h-10 w-10" />

			<h2 class="mb-3 text-lg font-semibold">
				{__("Add ways for customers to spend credits")}
			</h2>

			<p>
				{__(
					"Create and manage spend rules to allow customers to spend their credits.",
					"leat-crm"
				)}
			</p>
		</div>

		<Card.Root class="col-span-6 sm:order-2 sm:col-span-5">
			<Card.Header class="flex  items-center justify-between sm:flex-row">
				<div class="grid gap-2">
					<Card.Title>{__("Spend rules")}</Card.Title>

					<Card.Description>
						{__("Create and manage spend rules")}
					</Card.Description>
				</div>

				<div class="flex items-center justify-between gap-2">
					<Button
						size="sm"
						variant="secondary"
						href="https://business.leat.eu/loyalty"
						target="_blank"
						rel="noopener noreferrer"
					>
						{__("View in Leat")}
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
		</Card.Root>
	</div>
{/if}
