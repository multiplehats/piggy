<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { derived } from "svelte/store";
	import { __ } from "@wordpress/i18n";
	import { ChevronDown } from "lucide-svelte";
	import { Badge } from "$lib/components/ui/badge/index.js";
	import { Button } from "$lib/components/ui/button/index.js";
	import { Progress } from "$lib/components/ui/progress/index.js";
	import type { TaskInformation } from "$lib/modules/settings/types";
	import * as Card from "$lib/components/ui/card/index.js";

	export let key: string;
	export let title: string;
	export let mutationFn: () => Promise<any>;
	export let showButton = true;
	export let queryFn: () => Promise<TaskInformation>;
	export let onMutationSuccess: (() => void) | undefined = undefined;

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
		}))
	);

	$: {
		isStillSyncing =
			($mutateSync.isPending ||
				$querySyncInformation.data?.is_processing ||
				$querySyncInformation.data?.is_queued) ??
			false;
	}
</script>

<Card.Root>
	<Card.Header>
		<Card.Title>{title}</Card.Title>
	</Card.Header>

	<Card.Content>
		{#if showButton}
			<Button
				variant="primary"
				size="sm"
				loading={$mutateSync.isPending}
				on:click={() => $mutateSync.mutate()}
				disabled={isStillSyncing}
				class="w-full"
			>
				{__("Sync now", "leat")}
			</Button>
		{/if}

		{#if $querySyncInformation.isSuccess && $querySyncInformation.data}
			<div class="mt-2 grid gap-3">
				{#if isStillSyncing}
					<div class="flex flex-col items-start gap-1.5 text-sm">
						<Badge class="font-xs">
							{$querySyncInformation.data.status}
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

					{#if showDetails && $querySyncInformation.data.last_process}
						<div class="space-y-2 font-mono text-xs">
							<div class="flex flex-col items-start justify-between gap-0.5">
								<span class="text-muted-foreground font-bold">
									{__("Last sync", "leat")}
								</span>

								<span>
									{new Date(
										$querySyncInformation.data.last_process.timestamp * 1000
									).toLocaleString()}
								</span>
							</div>

							<div class="flex flex-col items-start justify-between gap-0.5">
								<span class="text-muted-foreground font-bold">
									{__("Items processed", "leat")}
								</span>

								<span>
									{$querySyncInformation.data.last_process.items_processed} / {$querySyncInformation
										.data.last_process.items_processed}
								</span>
							</div>
						</div>
					{/if}
				{/if}
			</div>
		{/if}
	</Card.Content>
</Card.Root>
