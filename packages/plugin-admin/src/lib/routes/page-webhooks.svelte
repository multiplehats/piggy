<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import { AlertTriangle } from "lucide-svelte";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { MutationKeys, QueryKeys } from "$lib/utils/query-keys";
	import * as Card from "$lib/components/ui/card/index.js";
	import { Badge } from "$lib/components/ui/badge/index.js";
	import { Button } from "$lib/components/ui/button";
	import { Alert } from "$lib/components/ui/alert";

	const service = new SettingsAdminService();
	const client = useQueryClient();

	const query = createQuery({
		queryKey: [QueryKeys.webhooks],
		queryFn: async () => await service.getWebhooks(),
	});

	const syncWebhooksMutation = createMutation({
		mutationFn: async () => await service.syncWebhooks(),
		mutationKey: [MutationKeys.syncWebhooks],
		onSuccess: () => {
			client.invalidateQueries({ queryKey: [QueryKeys.webhooks] });
		},
	});

	// strip WordPress: from each wh name
	function formatWebhookName(name: string) {
		return name.replace("WordPress:", "").trim();
	}

	$: currentUrl = window.leatWcSettings?.homeUrl ?? "";
	$: canSync = !$syncWebhooksMutation.isPending && !$query.isLoading;
</script>

{#if $query.isError}
	<p>Error: {$query.error.message}</p>
{:else if $query.isSuccess}
	<div class="grid grid-cols-8 gap-6">
		<div class="col-span-8 sm:order-1 sm:col-span-2 sm:mt-2">
			<h2 class="mb-3 text-lg font-semibold">
				{__("Webhooks")}
			</h2>

			<p class="mb-2">
				{__(
					"Webhooks keep your WordPress site automatically synchronized with your Leat loyalty program settings and customer data."
				)}
			</p>

			<p class="text-muted-foreground/75 mb-4 text-xs">
				{__(
					"When changes are made in your Leat account (like updating a Voucher), webhooks ensure these changes are instantly reflected on your WordPress site."
				)}
			</p>

			<Card.Root>
				<Card.Header>
					<Card.Title>{__("Sync Webhooks", "leat")}</Card.Title>
				</Card.Header>

				<Card.Content>
					<Button
						size="sm"
						variant="secondary"
						disabled={!canSync}
						loading={$syncWebhooksMutation.isPending}
						on:click={() => $syncWebhooksMutation.mutate()}
						class="w-full"
					>
						{__("Sync Webhooks", "leat")}
					</Button>
				</Card.Content>
			</Card.Root>
		</div>

		<Card.Root class="col-span-8 sm:order-2 sm:col-span-6">
			<Card.Header class="flex flex-row items-center justify-between">
				<div>
					<Card.Title>{__("Active Webhooks", "leat")}</Card.Title>
					<Card.Description>
						{__("List of currently configured webhooks for your store.", "leat")}
					</Card.Description>
				</div>
			</Card.Header>

			<Card.Content>
				{#each Object.values($query.data?.required_webhooks || {}) as required_webhook}
					{#if !($query.data?.webhooks || []).some((webhook) => webhook.event_type === required_webhook.event_type && webhook.name === required_webhook.name)}
						<Alert
							class="mb-4"
							description={__(
								`Required webhook "${formatWebhookName(required_webhook.name)}" is missing. Please resync your webhooks.`,
								"leat-crm"
							)}
							type="error"
						/>
					{/if}
				{/each}

				{#if $query.data?.webhooks.length === 0}
					<div class="text-muted-foreground text-sm">
						{__("No webhooks configured.", "leat")}
					</div>
				{:else}
					<div class="space-y-4">
						{#each $query.data?.webhooks ?? [] as webhook}
							<div class="rounded-lg border p-4">
								<div class="flex items-start justify-between">
									<div class="space-y-1">
										<h4 class="font-medium">
											{formatWebhookName(webhook.name)}
										</h4>
										<p class="text-muted-foreground break-all text-sm">
											{webhook.url}
										</p>
										<div class="mt-2 space-y-2">
											{#if !webhook.url.startsWith(currentUrl)}
												<div class="text-warning flex items-center gap-2">
													<AlertTriangle class="h-4 w-4" />
													<span class="text-xs">
														{__(
															"URL doesn't match current site URL",
															"leat"
														)}
													</span>
												</div>
											{/if}
										</div>
									</div>
									<Badge
										variant={webhook.status === "active"
											? "default"
											: "secondary"}
									>
										{webhook.status}
									</Badge>
								</div>
								<div class="mt-2">
									<Badge variant="outline">
										{webhook.event_type}
									</Badge>
								</div>
							</div>
						{/each}
					</div>
				{/if}
			</Card.Content>
		</Card.Root>
	</div>
{/if}
