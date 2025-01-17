<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import ChevronLeft from "lucide-svelte/icons/chevron-left";
	import { useNavigate, useParams } from "svelte-navigator";
	import { derived, writable } from "svelte/store";
	import { Badge } from "$lib/components/ui/badge/index.js";
	import { Button } from "$lib/components/ui/button/index.js";
	import { Progress } from "$lib/components/ui/progress/index.js";
	import * as Card from "$lib/components/ui/card/index.js";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { upsertPromotionRuleMutationConfig } from "$lib/modules/settings/mutations";
	import type { GetPromotionRuleByIdResponse } from "$lib/modules/settings/types";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { getStatusText } from "$lib/utils/status-text";
	import SettingsSelect from "$lib/components/settings-select.svelte";
	import SettingsInput from "$lib/components/settings-input.svelte";
	import SettingsTranslateableInput from "$lib/components/settings-translateable-input.svelte";
	import PromotionRuleProductSelect from "$lib/components/promotions/promotion-rule-product-select.svelte";

	const service = new SettingsAdminService();
	const navigate = useNavigate();
	const params = useParams();
	const client = useQueryClient();
	const query = createQuery(
		derived(params, ($params) => ({
			queryKey: [QueryKeys.promotionRuleById, $params.id],
			retry: false,
			queryFn: async () => {
				const data = await service.getPromotionRuleById({ id: $params.id.toString() });

				if (!data?.length) {
					return null;
				}

				return data[0];
			},
			refetchOnWindowFocus: true,
			enabled: !!$params.id,
		}))
	);
	const mutate = createMutation(
		upsertPromotionRuleMutationConfig(
			client,
			{},
			{
				onSuccessCb: () => client.refetchQueries({ queryKey: [QueryKeys.promotionRules] }),
			}
		)
	);
	const mutateSync = createMutation({
		mutationFn: () => service.syncVouchers($params.id.toString()),
		mutationKey: ["sync-vouchers"],
		onSuccess: () => {
			$query.refetch();
		},
	});

	const querySyncInformation = createQuery(
		derived(mutateSync, ($mutateSync) => ({
			queryKey: ["sync-vouchers-information"],
			queryFn: () => service.getSyncVouchersInformation({ id: $params.id.toString() }),
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
	const rule = writable<GetPromotionRuleByIdResponse[0] | null>(null);

	let isStillSyncing = false;

	$: {
		isStillSyncing =
			($mutateSync.isPending ||
				$querySyncInformation.data?.is_processing ||
				$querySyncInformation.data?.is_queued) ??
			false;
	}

	function handleSave() {
		if (!$rule) {
			return;
		}

		const data = {
			id: $rule.id,
			title: $rule.title?.value ?? __("New rule", "leat"),
			label: $rule.label.value,
			status: $rule.status.value,
			selectedProducts: $rule?.selectedProducts?.value,
			discountValue: $rule?.discountValue?.value,
			discountType: $rule?.discountType?.value,
			minimumPurchaseAmount: $rule?.minimumPurchaseAmount?.value,
		};

		$mutate.mutate(data);
	}

	$: if ($query.data && $query.isSuccess) {
		rule.set($query.data);
	}
</script>

{#if $query.isError}
	<p>Error: {$query.error.message}</p>
{:else if $query.isSuccess && $rule}
	<div class="grid max-w-[59rem] flex-1 auto-rows-max gap-4">
		<div class="flex items-center gap-4">
			<Button
				variant="outline"
				size="icon"
				class="h-7 w-7"
				on:click={() => navigate("/promotions")}
			>
				<ChevronLeft class="h-4 w-4" />
				<span class="sr-only">Back</span>
			</Button>

			<h1 class="flex-1 shrink-0 whitespace-nowrap text-xl font-semibold sm:grow-0">
				<!--  eslint-disable-next-line svelte/no-at-html-tags -->
				{@html $rule.title.value}
			</h1>

			<Badge
				variant={$rule.status.value === "publish" ? "default" : "secondary"}
				class="ml-auto sm:ml-0"
			>
				{getStatusText($rule.status.value)}
			</Badge>

			<div class="hidden items-center gap-2 md:ml-auto md:flex">
				<Button size="sm" on:click={handleSave}>
					{__("Save rule", "leat")}
				</Button>
			</div>
		</div>

		<div class="grid gap-4 md:grid-cols-[1fr_250px] lg:grid-cols-3 lg:gap-8">
			<div class="grid auto-rows-max items-start gap-4 lg:col-span-2 lg:gap-8">
				<Card.Root>
					<Card.Header>
						<Card.Title>{__("General", "leat")}</Card.Title>
					</Card.Header>

					<Card.Content>
						<div class="grid gap-6">
							<SettingsInput
								{...$rule.title}
								bind:value={$rule.title.value}
								readonly={true}
							/>

							<SettingsTranslateableInput
								{...$rule.label}
								bind:value={$rule.label.value}
							/>

							<SettingsInput
								{...$rule.voucherLimit}
								bind:value={$rule.voucherLimit.value}
								readonly={true}
							/>

							<SettingsInput
								{...$rule.limitPerContact}
								bind:value={$rule.limitPerContact.value}
								readonly={true}
							/>

							<SettingsInput
								{...$rule.minimumPurchaseAmount}
								type="number"
								attributes={{ min: 0 }}
								bind:value={$rule.minimumPurchaseAmount.value}
							></SettingsInput>

							<PromotionRuleProductSelect selectedProducts={$rule.selectedProducts} />
						</div>
					</Card.Content>
				</Card.Root>
			</div>

			<div class="grid auto-rows-max items-start gap-4 lg:gap-8">
				<Card.Root>
					<Card.Header>
						<Card.Title>{__("Details xqwreqwr", "leat")}</Card.Title>
					</Card.Header>

					<Card.Content>
						<div class="grid gap-6">
							<SettingsSelect
								{...$rule.status}
								bind:value={$rule.status.value}
								items={Object.entries($rule.status.options).map(
									([value, { label: name }]) => {
										return {
											value,
											name,
										};
									}
								)}
							/>
						</div>
					</Card.Content>
				</Card.Root>

				<Card.Root>
					<Card.Header>
						<Card.Title>{__("Sync vouchers", "leat")}</Card.Title>
					</Card.Header>

					<Card.Content>
						<div class="grid gap-4">
							<Button
								loading={$mutateSync.isPending}
								on:click={() => $mutateSync.mutate()}
								disabled={isStillSyncing}
								class="w-full"
							>
								{__("Sync vouchers", "leat")}
							</Button>

							{#if $querySyncInformation.isSuccess && $querySyncInformation.data}
								<div class="grid gap-3">
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
									{:else if $querySyncInformation.data.last_process}
										<div class="text-sm">
											<div class="flex items-center justify-between">
												<span class="text-muted-foreground">
													{__("Last sync completed", "leat")}
												</span>
												<span class="font-medium">
													{new Date(
														$querySyncInformation.data.last_process.timestamp
													).toLocaleDateString()}
												</span>
											</div>
											<div class="mt-2 flex items-center justify-between">
												<span class="text-muted-foreground">
													{__("Items processed", "leat")}
												</span>
												<span class="font-medium">
													{$querySyncInformation.data.last_process
														.items_processed} / {$querySyncInformation
														.data.last_process.items_processed}
												</span>
											</div>
										</div>
									{/if}
								</div>
							{/if}
						</div>
					</Card.Content>
				</Card.Root>

				<!-- Disabled until this feature is implemented -->
				<!-- <Card.Root>
					<Card.Header>
						<Card.Title>{__('Schedule', 'leat')}</Card.Title>
					</Card.Header>

					<Card.Content>
						<div class="grid gap-6">
							<SettingsCalendar
								{...$rule.startsAt}
								bind:value={$rule.startsAt.value}
								placeholder={$rule.startsAt.value}
							/>
							<SettingsCalendar
								{...$rule.expiresAt}
								bind:value={$rule.expiresAt.value}
								placeholder={$rule.expiresAt.value}
							/>
						</div>
					</Card.Content>
				</Card.Root> -->
			</div>
		</div>

		<div class="flex items-center justify-center gap-2 md:hidden">
			<Button size="sm" on:click={handleSave}>
				{__("Save rule", "leat")}
			</Button>
		</div>
	</div>
{/if}
