<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { __, sprintf } from "@wordpress/i18n";
	import ChevronLeft from "lucide-svelte/icons/chevron-left";
	import { useNavigate, useParams } from "svelte-navigator";
	import { derived, writable } from "svelte/store";
	import EarnRulePlaceOrder from "$lib/components/earn-rules/earn-rule-place-order.svelte";
	import EarnRuleSocial from "$lib/components/earn-rules/earn-rule-social.svelte";
	import SettingsInput from "$lib/components/settings-input.svelte";
	import SettingsSelect from "$lib/components/settings-select.svelte";
	import SettingsTranslateableInput from "$lib/components/settings-translateable-input.svelte";
	import { Alert } from "$lib/components/ui/alert";
	import { Badge } from "$lib/components/ui/badge/index.js";
	import { Button } from "$lib/components/ui/button/index.js";
	import * as Card from "$lib/components/ui/card/index.js";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { upsertEarnRuleMutationConfig } from "$lib/modules/settings/mutations";
	import type { GetEarnRuleByIdResponse } from "$lib/modules/settings/types";
	import { QueryKeys } from "$lib/utils/query-keys";
	import { getStatusText } from "$lib/utils/status-text";

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
			enabled: !!$params.id,
		}))
	);
	const mutate = createMutation(
		upsertEarnRuleMutationConfig(
			client,
			{},
			{
				onSuccessCb: () => client.refetchQueries({ queryKey: [QueryKeys.earnRules] }),
			}
		)
	);
	const rule = writable<GetEarnRuleByIdResponse[0] | null>(null);

	function handleSave() {
		if (!$rule) {
			return;
		}

		$mutate.mutate({
			id: $rule.id,
			type: $rule.type.value,
			label: $rule.label.value,
			status: $rule.status.value,
			title: $rule.title?.value ?? __("New rule", "piggy"),
			startsAt: $rule.startsAt.value,
			expiresAt: $rule.expiresAt.value,
			minimumOrderAmount: $rule.minimumOrderAmount.value,
			credits: $rule.credits.value,
			socialHandle: $rule.socialHandle.value,
		});
	}

	$: if ($query.data && $query.isSuccess) {
		rule.set($query.data);
	}

	$: ruleTypeLabel = $rule?.type?.options[$rule?.type?.value]?.label ?? "";
</script>

{#if $rule && $query.isSuccess && $query.data}
	<div class="grid max-w-[59rem] flex-1 auto-rows-max gap-4">
		<div class="flex items-center gap-4">
			<Button
				variant="outline"
				size="icon"
				class="h-7 w-7"
				on:click={() => navigate("/loyalty-program")}
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

			{#if ruleTypeLabel}
				<Badge variant="outline" class="ml-auto sm:ml-0">
					{ruleTypeLabel}
				</Badge>
			{/if}

			<div class="hidden items-center gap-2 md:ml-auto md:flex">
				<Button size="sm" on:click={handleSave}>
					{__("Save rule", "piggy")}
				</Button>
			</div>
		</div>

		<div class="grid gap-4 md:grid-cols-[1fr_250px] lg:grid-cols-3 lg:gap-8">
			<div class="grid auto-rows-max items-start gap-4 lg:col-span-2 lg:gap-8">
				<Card.Root>
					<Card.Header>
						<Card.Title>{__("General", "piggy")}</Card.Title>
					</Card.Header>

					<Card.Content>
						<div class="grid gap-6">
							{#if $rule.type.value === "PLACE_ORDER"}
								<Alert title={__("Credit Calculation", "piggy")} type="info">
									<!--  eslint-disable-next-line svelte/no-at-html-tags -->
									{@html sprintf(
										__(
											'These settings control the appearance on your WordPress site. The actual credit calculation is configured in the <a href="%s" class="underline" target="_blank" rel="noopener noreferrer">Piggy Dashboard</a>.',
											"piggy"
										),
										"https://business.piggy.eu/loyalty/1/rules"
									)}
								</Alert>
							{/if}

							<div class="grid gap-3">
								<SettingsInput {...$rule.title} bind:value={$rule.title.value} />
							</div>

							<SettingsTranslateableInput
								{...$rule.label}
								bind:value={$rule.label.value}
							/>

							{#if $rule.type.value !== "PLACE_ORDER"}
								<SettingsInput
									{...$rule.credits}
									bind:value={$rule.credits.value}
								/>
							{/if}
						</div>
					</Card.Content>
				</Card.Root>

				{#if $rule?.type?.value === "PLACE_ORDER"}
					<EarnRulePlaceOrder bind:minimumOrderAmount={$rule.minimumOrderAmount} />
				{:else if $rule?.type.value === "LIKE_ON_FACEBOOK" || $rule?.type.value === "FOLLOW_ON_INSTAGRAM" || $rule?.type.value === "FOLLOW_ON_TIKTOK"}
					<EarnRuleSocial bind:socialHandle={$rule.socialHandle} />
				{/if}
			</div>

			<div class="grid auto-rows-max items-start gap-4 lg:gap-8">
				<Card.Root>
					<Card.Header>
						<Card.Title>{__("Details", "piggy")}</Card.Title>
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

							<SettingsSelect
								hidden={true}
								{...$rule.type}
								bind:value={$rule.type.value}
								items={Object.entries($rule.type.options).map(
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
				{__("Save rule", "piggy")}
			</Button>
		</div>
	</div>
{/if}
