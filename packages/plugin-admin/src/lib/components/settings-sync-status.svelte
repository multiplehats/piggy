<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { derived } from "svelte/store";
	import { __ } from "@wordpress/i18n";
	import { ChevronDown } from "lucide-svelte";
	import { Badge } from "$lib/components/ui/badge/index.js";
	import { Button } from "$lib/components/ui/button/index.js";
	import { Progress } from "$lib/components/ui/progress/index.js";
	import type { SyncStatus, TaskInformation } from "$lib/modules/settings/types";
	import * as Card from "$lib/components/ui/card/index.js";

	export let key: string;
	export let title: string;
	export let mutationFn: () => Promise<any>;
	export let showButton = true;
	export let queryFn: () => Promise<TaskInformation | SyncStatus> | null = () => null;
	export let onMutationSuccess: (() => void) | null = null;
	export let disabled = false;

	let isStillSyncing = false;
	let showDetails = false;

	const client = useQueryClient();
	const mutateSync = createMutation({
		mutationFn: () => mutationFn(),
		mutationKey: [`sync-${key}`],
		onSuccess: () => {
			client.refetchQueries({ queryKey: [`sync-${key}`] });

			showDetails = true;

			onMutationSuccess?.();
		},
	});

	const querySyncInformation = createQuery(
		derived(mutateSync, ($mutateSync) => ({
			queryKey: [`sync-${key}`],
			queryFn: () => queryFn(),
			// @ts-expect-error -- Because of derived, this is not a valid type
			refetchInterval: (query) => {
				if (
					query.state.data?.is_queued ||
					query.state.data?.is_processing ||
					$mutateSync.isPending
				) {
					return 2000;
				}

				return false;
			},
			enabled: !!queryFn,
		}))
	);

	$: {
		isStillSyncing =
			($mutateSync.isPending ||
				($querySyncInformation.data?.type === "background_task" &&
					$querySyncInformation.data?.is_processing)) ??
			false;
	}
</script>

<Card.Root>
	<Card.Header>
		<Card.Title>{title}</Card.Title>
	</Card.Header>

	<Card.Content>
		{#if showButton}
			<div class="flex gap-2">
				<Button
					size="sm"
					variant="secondary"
					loading={$mutateSync.isPending}
					on:click={() => $mutateSync.mutate()}
					disabled={$mutateSync.isPending || disabled}
					class="w-full"
				>
					{__("Sync now", "leat")}
				</Button>

				<!-- {#if disabled || isStillSyncing}
					<Button
						size="sm"
						variant="outline"
						loading={$mutateSync.isPending}
						on:click={() => $mutateSync.mutate()}
						disabled={$mutateSync.isPending}
						class="shrink-0"
					>
						{__("Force sync", "leat")}
					</Button>
				{/if} -->
			</div>
		{/if}

		{#if $querySyncInformation.isSuccess && $querySyncInformation.data}
			<div class="mt-2 grid gap-3">
				{#if isStillSyncing}
					<div class="flex flex-col items-start gap-1.5 text-sm">
						<Badge class="font-xs">
							{#if $querySyncInformation.data.type === "background_task"}
								{$querySyncInformation.data.status}
							{:else}
								{__("Syncing...", "leat")}
							{/if}
						</Badge>

						<span class="text-muted-foreground text-xs">
							{$querySyncInformation.data.items_processed} / {$querySyncInformation
								.data.total_items}
							{__("items processed", "leat")}
						</span>
					</div>

					<Progress
						max={$querySyncInformation.data.total_items}
						value={$querySyncInformation.data.items_processed}
					/>
				{:else}
					<Button
						variant="link"
						size="sm"
						class="h-6 justify-start py-0.5 pl-0 pr-0.5"
						on:click={() => (showDetails = !showDetails)}
					>
						<span>{__("View details", "leat")}</span>
						<ChevronDown
							class={showDetails
								? "rotate-180 transition-transform"
								: "transition-transform"}
						/>
					</Button>

					{#if showDetails}
						<div class="space-y-2 font-mono text-xs">
							<div class="flex flex-col items-start justify-between gap-0.5">
								<span class="text-muted-foreground font-bold">
									{__("Last sync", "leat")}
								</span>

								<span>
									{#if $querySyncInformation.data.type === "background_task" && $querySyncInformation.data.last_process?.timestamp}
										{new Date(
											$querySyncInformation.data.last_process.timestamp * 1000
										).toLocaleString()}
									{:else if $querySyncInformation.data.type === "sync" && $querySyncInformation.data.last_sync?.timestamp}
										{new Date(
											$querySyncInformation.data.last_sync.timestamp * 1000
										).toLocaleString()}
									{:else}
										{__("Never", "leat")}
									{/if}
								</span>
							</div>

							<div class="flex flex-col items-start justify-between gap-0.5">
								<span class="text-muted-foreground font-bold">
									{__("Items processed", "leat")}
								</span>

								<span>
									{#if $querySyncInformation.data.type === "background_task"}
										{$querySyncInformation.data.last_process?.items_processed ??
											0} / {$querySyncInformation.data.last_process
											?.items_processed ?? 0}
									{:else}
										{$querySyncInformation.data.items_processed ?? 0} / {$querySyncInformation
											.data.total_items ?? 0}
										{#if ($querySyncInformation.data.items_updated ?? 0) > 0}
											<br />({$querySyncInformation.data.items_updated}
											{__("updated", "leat")})
										{/if}
										{#if ($querySyncInformation.data.items_created ?? 0) > 0}
											<br />({$querySyncInformation.data.items_created}
											{__("created", "leat")})
										{/if}
										{#if ($querySyncInformation.data.items_deleted ?? 0) > 0}
											<br />({$querySyncInformation.data.items_deleted}
											{__("deleted", "leat")})
										{/if}
									{/if}
								</span>
							</div>

							{#if $querySyncInformation.data.type === "sync" && $querySyncInformation.data.last_sync?.error}
								<div class="flex flex-col items-start justify-between gap-0.5">
									<span class="text-muted-foreground font-bold">
										{__("Error", "leat")}
									</span>
									<span class="text-destructive">
										{$querySyncInformation.data.last_sync.error}
									</span>
								</div>
							{/if}
						</div>
					{/if}
				{/if}
			</div>
		{/if}
	</Card.Content>
</Card.Root>
