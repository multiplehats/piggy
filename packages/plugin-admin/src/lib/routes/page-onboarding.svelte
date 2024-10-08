<script lang="ts">
	import { createMutation, createQuery, useQueryClient } from "@tanstack/svelte-query";
	import { useNavigate } from "svelte-navigator";
	import OnboardingActions from "$lib/components/onboarding/onboarding-actions.svelte";
	import OnboardingSteps from "$lib/components/onboarding/onboarding-steps.svelte";
	import SettingsLogo from "$lib/components/settings-logo.svelte";
	import { SettingsAdminService } from "$lib/modules/settings";
	import { saveSettingsMutationConfig } from "$lib/modules/settings/mutations";
	import { onboardingSteps, useOnboarding } from "$lib/stores/onboarding";
	import { settingsState } from "$lib/stores/settings";
	import { QueryKeys } from "$lib/utils/query-keys";

	// eslint-disable-next-line no-unused-expressions
	$$restProps;

	const navigate = useNavigate();
	const onboarding = useOnboarding();
	const client = useQueryClient();
	const service = new SettingsAdminService();
	const saveSettingsMutation = createMutation(
		saveSettingsMutationConfig(client, {
			onSuccess: async () => {
				await client.invalidateQueries({ queryKey: [QueryKeys.piggyShops] });

				const isLastStep = onboarding.isLastStep();

				if (isLastStep) {
					navigate("/general", {
						state: {
							onboarding: "complete",
						},
					});
					return;
				}

				const { href } = onboarding.nextStep();

				navigate(href);
			},
		})
	);
	const query = createQuery({
		queryKey: [QueryKeys.settings],
		retry: false,
		queryFn: async () => await service.getAllSettings(),
		refetchOnWindowFocus: true,
	});

	$: if ($query.data && $query.isSuccess) {
		settingsState.set($query.data);
	}

	function handleSubmit(
		e: SubmitEvent & {
			currentTarget: EventTarget & HTMLFormElement;
		}
	) {
		e.preventDefault();

		$saveSettingsMutation.mutateAsync(settingsState);
	}
</script>

<div class="mx-auto max-w-4xl py-24">
	<div class="rounded-lg border p-8">
		<div class="mb-8 flex w-full items-center justify-center">
			<SettingsLogo />
		</div>

		<OnboardingSteps class="mb-8" />

		<form method="POST" on:submit={handleSubmit}>
			{#each $onboardingSteps as { id, component, status, showActions, initialising } (id)}
				{#if status === "current"}
					{#if component}
						<svelte:component this={component} />
					{:else}
						<p>Onboarding component for step {id} not found.</p>
					{/if}

					{#if showActions}
						<OnboardingActions
							saving={$saveSettingsMutation.isPending}
							disabled={initialising}
						/>
					{/if}
				{/if}
			{/each}
		</form>
	</div>
</div>
