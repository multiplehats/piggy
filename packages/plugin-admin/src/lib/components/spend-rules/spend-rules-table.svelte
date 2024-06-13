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
	import { upsertEarnRuleMutationConfig } from '$lib/modules/settings/mutations';
	import { getEarnRulesQueryConfig } from '$lib/modules/settings/queries';
	import { getStatusText } from '$lib/utils/status-text';
	import { WalletMinimal } from 'lucide-svelte';
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
				onSuccessCb: (earnRule) => {
					$query.refetch();

					navigate(`earn-rules/${earnRule.id}`);
				}
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

	let title: string | undefined = undefined;
	let selected: (typeof ruleTypes)[number] | undefined = undefined;
	let titleError = '';
	let ruleTypeError = '';

	const restrictedRuleTypes = [
		'LIKE_ON_FACEBOOK',
		'FOLLOW_ON_TIKTOK',
		'FOLLOW_ON_INSTAGRAM',
		'CELEBRATE_BIRTHDAY',
		'CREATE_ACCOUNT'
	] as const;

	$: existingRuleTypes = $query.data?.map((rule) => rule.type.value) || [];

	$: allowedRules = ruleTypes.filter((type) => {
		if (type.value === 'PLACE_ORDER') {
			return true;
		}
		return !restrictedRuleTypes.includes(type.value) || !existingRuleTypes.includes(type.value);
	});

	function validateForm() {
		titleError = title ? '' : __('Title is required.');
		ruleTypeError = selected ? '' : __('Rule type is required.');
		if (selected && existingRuleTypes.includes(selected.value)) {
			ruleTypeError = __('This rule type has already been created.');
		}
	}

	function handleCreateRule(event: Event) {
		event.preventDefault();
		validateForm();

		if (!title || !selected || ruleTypeError) {
			return;
		}

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

			<h2 class="text-lg font-semibold mb-3">{__('Add ways for customers to earn credits')}</h2>

			<p>
				{__(
					'Create rules that reward customers with credits when they perform certain actions. For example, you can reward customers with credits when they create an account or place an order.',
					'piggy'
				)}
			</p>
		</div>

		<Card.Root class="col-span-6 sm:col-span-5 sm:order-2">
			<Card.Header class="flex  sm:flex-row items-center justify-between">
				<div class="grid gap-2">
					<Card.Title>{__('Earn rules')}</Card.Title>

					<Card.Description>
						{__('Create and manage earn rules')}
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

							<form on:submit={handleCreateRule}>
								<div class="grid gap-4 py-4">
									<div class="grid gap-3">
										<Label for="title">
											{__('Title (Only visible to you)')}

											{#if titleError}
												<div class="text-red-600 mt-2">{titleError}</div>
											{/if}
										</Label>
										<Input class="col-span-3" name="title" bind:value={title} />
									</div>

									<div class="grid gap-3">
										<Label for="ruleType">
											{__('Rule type')}

											{#if ruleTypeError}
												<div class="text-red-600 mt-2">{ruleTypeError}</div>
											{/if}
										</Label>

										<Select.Root
											name="ruleType"
											items={ruleTypes.filter((type) => !existingRuleTypes.includes(type.value))}
											bind:selected
										>
											<Select.Trigger class="w-[180px]">
												<Select.Value placeholder={__('Select type')} />
											</Select.Trigger>
											<Select.Content>
												<Select.Group>
													<Select.Label>
														{__('Select type')}
													</Select.Label>

													{#each allowedRules as type}
														<Select.Item value={type.value} label={type.label}>
															{type.label}
														</Select.Item>
													{/each}
												</Select.Group>
											</Select.Content>

											<Select.Input name="ruleType" />
										</Select.Root>
									</div>
								</div>

								<Dialog.Footer>
									<Button type="submit">
										{__('Create')}
									</Button>
								</Dialog.Footer>
							</form>
						</Dialog.Content>
					</Dialog.Root>
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

								<Table.Cell class="text-right">
									<Badge variant={rule.status.value === 'publish' ? 'default' : 'secondary'}
										>{getStatusText(rule.status.value)}</Badge
									>
								</Table.Cell>
							</Table.Row>
						{/each}
					</Table.Body>
				</Table.Root>
			</Card.Content>
		</Card.Root>
	</div>
{/if}
