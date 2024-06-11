<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from '@tanstack/svelte-query';
	import { __ } from '@wordpress/i18n';
	import EarnRulePlaceOrder from '$lib/components/earn-rules/earn-rule-place-order.svelte';
	import SettingsCalendar from '$lib/components/settings-calendar.svelte';
	import SettingsInput from '$lib/components/settings-input.svelte';
	import SettingsSelect from '$lib/components/settings-select.svelte';
	import SettingsTranslateableInput from '$lib/components/settings-translateable-input.svelte';
	import { Badge } from '$lib/components/ui/badge/index.js';
	import { Button } from '$lib/components/ui/button/index.js';
	import * as Card from '$lib/components/ui/card/index.js';
	import { SettingsAdminService } from '$lib/modules/settings';
	import { upsertEarnRuleMutationConfig } from '$lib/modules/settings/mutations';
	import type { GetEarnRuleByIdResponse } from '$lib/modules/settings/types';
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
			queryKey: [QueryKeys.earnRuleById, $params.id],
			retry: false,
			queryFn: async () => {
				const data = await service.getEarnRuleById({ id: $params.id.toString() });

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
		upsertEarnRuleMutationConfig(
			client,
			{},
			{
				// onSuccessCb: () => navigate('/loyalty-program')
			}
		)
	);
	const rule = writable<GetEarnRuleByIdResponse[0] | null>(null);

	function handleSave() {
		if (!$rule) {
			return;
		}

		console.log($rule);

		$mutate.mutate({
			id: $rule.id,
			type: $rule.type.value,
			label: $rule.label.value,
			status: $rule.status.value,
			title: $rule.title.value,
			startsAt: $rule.startsAt.value,
			expiresAt: $rule.expiresAt.value
		});
	}

	$: if ($query.data && $query.isSuccess) {
		console.log('Incoming data: ', $query.data);

		rule.set($query.data);
	}
</script>

{#if $rule && $query.isSuccess && $query.data}
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

			<Badge variant="outline" class="ml-auto sm:ml-0">
				{getStatusText($rule.status.value)}
			</Badge>

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
							<div class="grid gap-3">
								<SettingsInput {...$rule.title} bind:value={$rule.title.value} />
							</div>

							<SettingsTranslateableInput {...$rule.label} bind:value={$rule.label.value} />
						</div>
					</Card.Content>
				</Card.Root>

				{#if $rule?.type?.value === 'PLACE_ORDER'}
					<EarnRulePlaceOrder />
				{/if}
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

				<Card.Root>
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
							{$rule.expiresAt.value}
							<SettingsCalendar
								{...$rule.expiresAt}
								bind:value={$rule.expiresAt.value}
								placeholder={$rule.expiresAt.value}
							/>
						</div>
					</Card.Content>
				</Card.Root>
			</div>
		</div>

		<div class="flex items-center justify-center gap-2 md:hidden">
			<Button size="sm">
				{__('Save rule', 'piggy')}
			</Button>
		</div>
	</div>
{/if}
