<script lang="ts">
	import { outboundUrl } from '@piggy/lib';
	import { __ } from '@wordpress/i18n';
	import { Button } from '$lib/components/ui/button/index.js';
	import * as Card from '$lib/components/ui/card/index.js';
	import { useNavigate } from 'svelte-navigator';

	const navigate = useNavigate();

	const options = [
		{
			id: 'connect-account',
			variant: 'primary',
			title: __('Existing PIGGY user?', 'piggy'),
			description: __('Connect your PIGGY account to your store to get started.', 'piggy'),
			cta: 'Connect account',
			action: () => navigate('/onboarding/connect-account')
		},
		{
			id: 'create-account',
			variant: 'secondary',
			title: __('New to PIGGY?', 'piggy'),
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
