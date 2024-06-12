<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import { Button, buttonVariants } from '$lib/components/ui/button';
	import * as Card from '$lib/components/ui/card/index.js';
	import * as Dialog from '$lib/components/ui/dialog/index.js';
	import { Input } from '$lib/components/ui/input/index.js';
	import { Label } from '$lib/components/ui/label/index.js';
	import * as Select from '$lib/components/ui/select';
	import * as Table from '$lib/components/ui/table/index.js';
	import { upsertEarnRuleMutationConfig } from '$lib/modules/settings/mutations';
	import { getEarnRulesQueryConfig } from '$lib/modules/settings/queries';
	import { useNavigate } from 'svelte-navigator';
	import type { EarnRuleType } from '@piggy/types/plugin/settings/adminTypes';

	const navigate = useNavigate();
	const client = useQueryClient();
	const query = createQuery(getEarnRulesQueryConfig());
	const mutate = createMutation(
		upsertEarnRuleMutationConfig(
			client,
			{},
			{
				onSuccessCb: (earnRule) => navigate(`earn-rules/${earnRule.id}`)
			}
		)
	);

	const ruleTypes = [
		{ label: 'Like on Facebook', value: 'LIKE_ON_FACEBOOK' },
		{ label: 'Follow on TikTok', value: 'FOLLOW_ON_TIKTOK' },
		{ label: 'Follow on Instagram', value: 'FOLLOW_ON_INSTAGRAM' },
		{ label: 'Place an order', value: 'PLACE_ORDER' },
		{ label: 'Celebrate your birthday', value: 'CELEBRATE_BIRTHDAY' },
		{ label: 'Create an account', value: 'CREATE_ACCOUNT' }
	] satisfies { label: string; value: EarnRuleType }[];

	let title = '';
	let selected = ruleTypes[0];

	function handleCreateRule(event: Event) {
		event.preventDefault();

		$mutate.mutate({
			title,
			type: selected.value
		});
	}
</script>

{#if $query?.data}
	<Card.Root class="xl:col-span-2">
		<Card.Header class="flex flex-row items-center justify-between">
			<div class="grid gap-2">
				<Card.Title>{__('Earn rules')}</Card.Title>

				<Card.Description>
					{__('Create and manage earn rules')}
				</Card.Description>
			</div>

			<Dialog.Root>
				<Dialog.Trigger class={buttonVariants({ variant: 'default', size: 'sm' })}>
					{__('Create new rule')}
				</Dialog.Trigger>

				<Dialog.Content>
					<Dialog.Header>
						<Dialog.Title>
							{__('Create new rule')}
						</Dialog.Title>
					</Dialog.Header>

					<div class="grid gap-4 py-4">
						<div class="grid gap-3">
							<Label for="title">
								{__('Title (Only visible to you)')}
							</Label>

							<Input class="col-span-3" name="title" bind:value={title} />
						</div>

						<div class="grid gap-3">
							<Label for="roleType">Type</Label>

							<Select.Root name="roleType" items={ruleTypes} bind:selected>
								<Select.Trigger class="w-[180px]">
									<Select.Value placeholder={__('Select type')} />
								</Select.Trigger>
								<Select.Content>
									<Select.Group>
										<Select.Label>
											{__('Select type')}
										</Select.Label>

										{#each ruleTypes as type}
											<Select.Item value={type.value} label={type.label}>{type.label}</Select.Item>
										{/each}
									</Select.Group>
								</Select.Content>

								<Select.Input name="ruleType" />
							</Select.Root>
						</div>
					</div>

					<Dialog.Footer>
						<Button type="submit" on:click={handleCreateRule}>
							{__('Create')}
						</Button>
					</Dialog.Footer>
				</Dialog.Content>
			</Dialog.Root>
		</Card.Header>

		<Card.Content>
			<Table.Root>
				<Table.Header>
					<Table.Row>
						<Table.Head>Title</Table.Head>

						<Table.Head>Created At</Table.Head>
						<Table.Head class="text-right">Points</Table.Head>
					</Table.Row>
				</Table.Header>
				<Table.Body>
					{#each $query.data as rule}
						<Table.Row class="cursor-pointer" on:click={() => navigate(`earn-rules/${rule.id}`)}>
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

							<Table.Cell class="text-right">{rule.points ?? 'N/A'}</Table.Cell>
						</Table.Row>
					{/each}
				</Table.Body>
			</Table.Root>
		</Card.Content>
	</Card.Root>
{/if}
