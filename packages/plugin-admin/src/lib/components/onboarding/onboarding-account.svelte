<script lang="ts">
	import { __ } from '@wordpress/i18n';
	import { Button } from '$lib/components/ui/button/index.js';
	import * as Card from '$lib/components/ui/card/index.js';
	import { useOnboarding } from '$lib/stores/onboarding';
	import { useNavigate } from 'svelte-navigator';
	import { outboundUrl } from '@piggy/lib';

	const navigate = useNavigate();
	const onboarding = useOnboarding();

	const options = [
		{
			id: 'connect-account',
			variant: 'primary',
			title: __('Existing Piggy user?', 'piggy'),
			description: __('Connect your account to get started.', 'piggy'),
			cta: 'Connect account',
			action: () => {
				const { href } = onboarding.goToStep('connect-account');

				navigate(href);
			}
		},
		{
			id: 'create-account',
			variant: 'secondary',
			title: __('New to Piggy?', 'piggy'),
			description: __('Create a new account to get started.', 'piggy'),
			cta: 'Create an account',
			link: outboundUrl({
				url: 'https://business.piggy.eu/sign-up',
				source: 'woocommerce',
				medium: 'onboarding',
				campaign: 'wp-plugin'
			})
		}
	];
</script>

<div class="grid gap-4 grid-cols-2">
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
