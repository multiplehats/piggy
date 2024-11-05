<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __ } from "@wordpress/i18n";
	// import SettingsCalendar from '$lib/components/settings-calendar.svelte';
	import ChevronLeft from "lucide-svelte/icons/chevron-left";
	import { useNavigate, useParams } from "svelte-navigator";
	import { derived, writable } from "svelte/store";
	// import SpendRuleOrderDiscountFields from '$lib/components/spend-rules/spend-rule-order-discount-fields.svelte';
	// import SpendRuleRewardSelect from '$lib/components/spend-rules/spend-rule-reward-select.svelte';
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
	const rule = writable<GetPromotionRuleByIdResponse[0] | null>(null);

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

{#if $query.isLoading}
	<p>Loading...</p>
{:else if $query.isError}
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
						<Card.Title>{__("Details", "leat")}</Card.Title>
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
{:else}
	<p>Rule not found</p>
{/if}
