<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	import ChevronLeft from "lucide-svelte/icons/chevron-left";
	import { useNavigate, useParams } from "svelte-navigator";
	import { derived, writable } from "svelte/store";
	import { Badge } from "$lib/components/ui/badge/index.js";
	import { Button } from "$lib/components/ui/button/index.js";
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
	import SettingsSyncStatus from "$lib/components/settings-sync-status.svelte";
	import PromotionRuleProductDiscountFields from "$lib/components/promotions/promotion-rule-product-discount-fields.svelte";
	import SettingsSwitch from "$lib/components/settings-switch.svelte";

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
			refetchInterval: (query: any) => {
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
				<Button
					size="sm"
					on:click={handleSave}
					loading={$mutate.isPending}
					disabled={$mutate.isPending}
				>
					{__("Save rule", "leat-crm")}
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

							<SettingsSwitch
								{...$rule.individualUse}
								bind:value={$rule.individualUse.value}
								showErrors={false}
							/>

							<PromotionRuleProductDiscountFields
								bind:discountType={$rule.discountType}
								bind:discountValue={$rule.discountValue}
							/>

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

				<div>
					<SettingsSyncStatus
						key="vouchers"
						title={__("Sync vouchers", "leat")}
						mutationFn={() => service.syncVouchers($params.id.toString())}
						queryFn={() =>
							service.getSyncVouchersInformation({ id: $params.id.toString() })}
						onMutationSuccess={() => $query.refetch()}
						disabled={$rule.status.value !== "publish"}
					/>

					{#if $rule.status.value !== "publish"}
						<p class="text-muted-foreground/75 mt-2 px-4 text-center text-sm">
							{__("You can only sync vouchers when the rule is published.", "leat")}
						</p>
					{/if}
				</div>
			</div>
		</div>

		<div class="flex items-center justify-center gap-2 md:hidden">
			<Button
				size="sm"
				on:click={handleSave}
				loading={$mutate.isPending}
				disabled={$mutate.isPending}
			>
				{__("Save rule", "leat-crm")}
			</Button>
		</div>
	</div>
{/if}
