<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { Badge } from '$lib/components/ui/badge';
	import { Button, buttonVariants } from '$lib/components/ui/button';
	import * as Card from '$lib/components/ui/card/index.js';
	import * as Dialog from '$lib/components/ui/dialog/index.js';
	import { Input } from '$lib/components/ui/input/index.js';
	import { Label } from '$lib/components/ui/label/index.js';
	import * as Select from '$lib/components/ui/select';
	import * as Table from '$lib/components/ui/table/index.js';
	import { SettingsAdminService } from '$lib/modules/settings';
	import { upsertSpendRuleMutationConfig } from '$lib/modules/settings/mutations';
	import { QueryKeys } from '$lib/utils/query-keys';
	import { getStatusText } from '$lib/utils/status-text';
	import { WalletMinimal } from 'lucide-svelte';
	import toast from 'svelte-french-toast';
	import { useNavigate } from 'svelte-navigator';
	import type { SpendRuleType } from '@piggy/types/plugin/settings/adminTypes';

	const service = new SettingsAdminService();
	const navigate = useNavigate();
	const client = useQueryClient();
	const query = createQuery({
		queryKey: [QueryKeys.spendRules],
		retry: false,
		queryFn: async () => await service.getSpendRules(),
		refetchOnWindowFocus: true
	});
	const mutate = createMutation(
		upsertSpendRuleMutationConfig(
			client,
			{},
			{
				onSuccessCb: (spendRule) => {
					$query.refetch();

					navigate(`spend-rules/${spendRule.id}`);
				}
			}
		)
	);
	const mutateSync = createMutation({
		mutationFn: () => service.syncRewards(),
		mutationKey: ['spend-rules-sync'],
		onSuccess: () => {
			$query.refetch();

			toast.success(__('Synced rewards'));
		}
	});

	const ruleTypes = [
		{ label: __('Product discount', 'piggy'), value: 'PRODUCT_DISCOUNT' },
		{ label: __('Order discount', 'piggy'), value: 'ORDER_DISCOUNT' },
		{ label: __('Free shipping', 'piggy'), value: 'FREE_SHIPPING' }
	] satisfies { label: string; value: SpendRuleType }[];

	let title: string | undefined = undefined;
	let selected: (typeof ruleTypes)[number] | undefined = undefined;
	let titleError = '';
	let ruleTypeError = '';

	function validateForm() {
		titleError = title ? '' : __('Title is required.');
		ruleTypeError = selected ? '' : __('Rule type is required.');
	}

	function handleCreateRule(event: Event) {
		event.preventDefault();
		validateForm();

		if (!title || !selected || ruleTypeError) {
			return;
		}

		console.log('Creating rule', title, selected);

		if (!titleError && !ruleTypeError) {
			$mutate.mutate({
				title,
				type: selected.value,
				status: 'draft'
			});
		}
	}
</script>

{#if $query?.data}
	<div class="grid grid-cols-6 gap-6">
		<div class="col-span-6 sm:col-span-1 sm:order-1 sm:mt-2">
			<WalletMinimal class="w-10 h-10 text-foreground/75 mb-4" />

			<h2 class="text-lg font-semibold mb-3">{__('Add ways for customers to spend credits')}</h2>

			<p>
				{__('Create and manage spend rules to allow customers to spend their credits.', 'piggy')}
			</p>
		</div>

		<Card.Root class="col-span-6 sm:col-span-5 sm:order-2">
			<Card.Header class="flex  sm:flex-row items-center justify-between">
				<div class="grid gap-2">
					<Card.Title>{__('Spend rules')}</Card.Title>

					<Card.Description>
						{__('Create and manage spend rules')}
					</Card.Description>
				</div>

				<div class="flex items-center justify-between gap-2">
					<Button
						size="sm"
						variant="secondary"
						href="https://business.piggy.eu/loyalty"
						target="_blank"
						rel="noopener noreferrer"
					>
						{__('View in Piggy')}
					</Button>

					<Button size="sm" target="_blank" on:click={() => $mutateSync.mutate()}>
						{__('Sync rewards')}
					</Button>
				</div>
			</Card.Header>

			<Card.Content>
				<Table.Root>
					<Table.Header>
						<Table.Row>
							<Table.Head>{__('Title', 'piggy')}</Table.Head>
							<Table.Head>{__('Created at', 'piggy')}</Table.Head>
							<Table.Head class="text-right">{__('Status', 'piggy')}</Table.Head>
						</Table.Row>
					</Table.Header>
					<Table.Body>
						{#each $query.data as rule}
							<Table.Row class="cursor-pointer" on:click={() => navigate(`spend-rules/${rule.id}`)}>
								<Table.Cell>
									<div class="font-medium">{@html rule.title.value}</div>
								</Table.Cell>

								<Table.Cell>
									{new Date(rule.createdAt).toLocaleDateString(undefined, {
										year: 'numeric',
										month: 'long',
										day: 'numeric'
									})}
								</Table.Cell>

								<Table.Cell class="text-right">
									<Badge variant={rule.status.value === 'publish' ? 'default' : 'secondary'}>
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
