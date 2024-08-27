<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	// import SettingsCalendar from '$lib/components/settings-calendar.svelte';
	import SettingsInput from '$lib/components/settings-input.svelte';
	import SettingsSelect from '$lib/components/settings-select.svelte';
	import SettingsTranslateableInput from '$lib/components/settings-translateable-input.svelte';
	import SpendRuleFreeProductFields from '$lib/components/spend-rules/spend-rule-free-product-fields.svelte';
	// import SpendRuleOrderDiscountFields from '$lib/components/spend-rules/spend-rule-order-discount-fields.svelte';
	import SpendRuleProductOrderDiscountFields from '$lib/components/spend-rules/spend-rule-product-order-discount-fields.svelte';
	import SpendRuleRewardSelect from '$lib/components/spend-rules/spend-rule-reward-select.svelte';
	import { Badge } from '$lib/components/ui/badge/index.js';
	import { Button } from '$lib/components/ui/button/index.js';
	import * as Card from '$lib/components/ui/card/index.js';
	import { SettingsAdminService } from '$lib/modules/settings';
	import { upsertSpendRuleMutationConfig } from '$lib/modules/settings/mutations';
	import type { GetSpendRuleByIdResponse } from '$lib/modules/settings/types';
	import { QueryKeys } from '$lib/utils/query-keys';
	import { getStatusText } from '$lib/utils/status-text';
	import ChevronLeft from 'lucide-svelte/icons/chevron-left';
	import { useNavigate, useParams } from 'svelte-navigator';
	import { derived, writable } from 'svelte/store';

	const service = new SettingsAdminService();
	const navigate = useNavigate();
	const params = useParams();
	const client = useQueryClient();
	const query = createQuery(
		derived(params, ($params) => ({
			queryKey: [QueryKeys.spendRuleById, $params.id],
			retry: false,
			queryFn: async () => {
				const data = await service.getSpendRuleById({ id: $params.id.toString() });

				if (!data?.length) {
					return null;
				}

				return data[0];
			},
			refetchOnWindowFocus: true,
			enabled: !!$params.id
		}))
	);
	const mutate = createMutation(
		upsertSpendRuleMutationConfig(
			client,
			{},
			{
				// onSuccessCb: () => navigate('/loyalty-program')
			}
		)
	);
	const rule = writable<GetSpendRuleByIdResponse[0] | null>(null);

	function handleSave() {
		if (!$rule) {
			return;
		}
		console.log($rule);
		$mutate.mutate({
			id: $rule.id,
			type: $rule.type.value,
			title: $rule.title?.value ?? __('New rule', 'piggy'),
			label: $rule.label.value,
			status: $rule.status.value,
			startsAt: $rule?.startsAt?.value,
			expiresAt: $rule?.expiresAt?.value,
			selectedReward: $rule?.selectedReward?.value,
			instructions: $rule?.instructions?.value,
			selectedProducts: $rule?.selectedProducts?.value,
			description: $rule?.description?.value,
			fulfillment: $rule?.fulfillment?.value,
			discountValue: $rule?.discountValue?.value,
			discountType: $rule?.discountType?.value,
			minimumPurchaseAmount: $rule?.minimumPurchaseAmount?.value
		});
	}

	$: if ($query.data && $query.isSuccess) {
		rule.set($query.data);
	}

	$: ruleTypeLabel = $rule?.type?.options[$rule?.type?.value]?.label ?? '';

	$: console.log($rule);
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
				on:click={() => navigate('/loyalty-program')}
			>
				<ChevronLeft class="h-4 w-4" />
				<span class="sr-only">Back</span>
			</Button>

			<h1 class="flex-1 shrink-0 whitespace-nowrap text-xl font-semibold sm:grow-0">
				{@html $rule.title.value}
			</h1>

			<Badge
				variant={$rule.status.value === 'publish' ? 'default' : 'secondary'}
				class="ml-auto sm:ml-0"
			>
				{getStatusText($rule.status.value)}
			</Badge>

			{#if ruleTypeLabel}
				<Badge variant="outline" class="ml-auto sm:ml-0">
					{ruleTypeLabel}
				</Badge>
			{/if}

			<div class="hidden items-center gap-2 md:ml-auto md:flex">
				<Button size="sm" on:click={handleSave}>
					{__('Save rule', 'piggy')}
				</Button>
			</div>
		</div>

		<div class="grid gap-4 md:grid-cols-[1fr_250px] lg:grid-cols-3 lg:gap-8">
			<div class="grid auto-rows-max items-start gap-4 lg:col-span-2 lg:gap-8">
				<Card.Root>
					<Card.Header>
						<Card.Title>{__('General', 'piggy')}</Card.Title>
					</Card.Header>

					<Card.Content>
						<div class="grid gap-6">
							<!-- type-->
							<SettingsSelect
								{...$rule.type}
								bind:value={$rule.type.value}
								items={Object.entries($rule.type.options).map(([value, { label: name }]) => {
									return {
										value,
										name
									};
								})}
							/>

							<SpendRuleRewardSelect bind:selectedReward={$rule.selectedReward} />

							<SettingsInput {...$rule.title} bind:value={$rule.title.value} readonly={true} />

							<SettingsTranslateableInput {...$rule.label} bind:value={$rule.label.value} />

							<SettingsTranslateableInput
								{...$rule.description}
								bind:value={$rule.description.value}
							/>

							<SettingsTranslateableInput
								{...$rule.instructions}
								bind:value={$rule.instructions.value}
							/>

							<SettingsTranslateableInput
								{...$rule.fulfillment}
								bind:value={$rule.fulfillment.value}
							/>

							<SettingsInput
								{...$rule.creditCost}
								readonly={true}
								bind:value={$rule.creditCost.value}
							/>

							{#if ($rule?.type?.value === 'ORDER_DISCOUNT' || $rule?.type?.value === 'FREE_PRODUCT') && $rule?.discountType && $rule?.discountValue}
								<SpendRuleProductOrderDiscountFields
									bind:discountType={$rule.discountType}
									bind:discountValue={$rule.discountValue}
								/>
							{/if}

							{#if ($rule?.type?.value === 'ORDER_DISCOUNT' || $rule?.type?.value === 'FREE_SHIPPING') && $rule.minimumPurchaseAmount}
								<SettingsInput
									{...$rule.minimumPurchaseAmount}
									type="number"
									attributes={{ min: 0 }}
									bind:value={$rule.minimumPurchaseAmount.value}
								></SettingsInput>
							{/if}

							{#if $rule?.type?.value === 'FREE_PRODUCT'}
								<SpendRuleFreeProductFields selectedProducts={$rule.selectedProducts} />
							{/if}
						</div>
					</Card.Content>
				</Card.Root>
			</div>

			<div class="grid auto-rows-max items-start gap-4 lg:gap-8">
				<Card.Root>
					<Card.Header>
						<Card.Title>{__('Details', 'piggy')}</Card.Title>
					</Card.Header>

					<Card.Content>
						<div class="grid gap-6">
							<SettingsSelect
								{...$rule.status}
								bind:value={$rule.status.value}
								items={Object.entries($rule.status.options).map(([value, { label: name }]) => {
									return {
										value,
										name
									};
								})}
							/>

							<SettingsSelect
								hidden={true}
								{...$rule.type}
								bind:value={$rule.type.value}
								items={Object.entries($rule.type.options).map(([value, { label: name }]) => {
									return {
										value,
										name
									};
								})}
							/>
						</div>
					</Card.Content>
				</Card.Root>

				<!-- Disabled until this feature is implemented -->
				<!-- <Card.Root>
					<Card.Header>
						<Card.Title>{__('Schedule', 'piggy')}</Card.Title>
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
				{__('Save rule', 'piggy')}
			</Button>
		</div>
	</div>
{:else}
	<p>Rule not found</p>
{/if}
