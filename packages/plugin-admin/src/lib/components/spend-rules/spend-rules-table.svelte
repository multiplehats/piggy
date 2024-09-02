<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { Badge } from '$lib/components/ui/badge';
	import { Button } from '$lib/components/ui/button';
	import * as Card from '$lib/components/ui/card/index.js';
	import * as Table from '$lib/components/ui/table/index.js';
	import { SettingsAdminService } from '$lib/modules/settings';
	import { QueryKeys } from '$lib/utils/query-keys';
	import { getStatusText } from '$lib/utils/status-text';
	import { WalletMinimal } from 'lucide-svelte';
	import toast from 'svelte-french-toast';
	import { useNavigate } from 'svelte-navigator';

	const service = new SettingsAdminService();
	const navigate = useNavigate();
	const client = useQueryClient();
	const query = createQuery({
		queryKey: [QueryKeys.spendRules],
		retry: false,
		queryFn: async () => await service.getSpendRules(),
		refetchOnWindowFocus: true
	});
	const mutateSync = createMutation({
		mutationFn: () =>
			toast.promise(service.syncRewards(), {
				loading: __('Syncing rewards...'),
				success: __('Rewards synced'),
				error: __('Failed to sync rewards')
			}),
		mutationKey: ['spend-rules-sync'],
		onSuccess: () => {
			$query.refetch();
		}
	});
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
