<script lang="ts">
	import { __ } from "@wordpress/i18n";
	import { useNavigate } from "svelte-navigator";
	import { outboundUrl } from "@leat/lib";
	import { Button } from "$lib/components/ui/button/index.js";
	import * as Card from "$lib/components/ui/card/index.js";
	import { OnboardingStepId, useOnboarding } from "$lib/stores/onboarding";

	const navigate = useNavigate();
	const onboarding = useOnboarding();

	const options = [
		{
			id: "connect-account-option",
			variant: "primary",
			title: __("Existing Leat user?", "leat"),
			description: __("Connect your account to get started.", "leat"),
			cta: "Connect account",
			action: () => {
				const { href } = onboarding.completeAndNavigate(
					OnboardingStepId.welcome,
					OnboardingStepId.connectAccount
				);
				navigate(href);
			},
		},
		{
			id: "create-account-option",
			variant: "secondary",
			title: __("New to Leat?", "leat"),
			description: __("Create a new account to get started.", "leat"),
			cta: "Create an account",
			link: outboundUrl({
				url: "https://business.leat.com/sign-up",
				source: "woocommerce",
				medium: "onboarding",
				campaign: "wp-plugin",
			}),
		},
	];
</script>

<div class="grid grid-cols-2 gap-4">
	{#each options as option (option.id)}
		<Card.Root>
			<Card.Header>
				<Card.Title class="text-xl">
					{option.title}
				</Card.Title>
				<Card.Description>
					{option.description}
				</Card.Description>
			</Card.Header>

			<Card.Content>
				{#if option?.link}
					<Button
						href={option.link}
						variant="secondary"
						class="w-full"
						target="_blank"
						rel="noopener noreferrer"
					>
						{option.cta}
					</Button>
				{:else}
					<Button on:click={option.action} class="w-full">
						{option.cta}
					</Button>
				{/if}
			</Card.Content>
		</Card.Root>
	{/each}
</div>
