<script lang="ts">
	import { cn } from '$lib/utils/cn.js';
	import { Button as ButtonPrimitive } from 'bits-ui';
	import { type Events, type Props } from './index.js';

	type $$Props = Props;
	type $$Events = Events;

	let className: $$Props['class'] = undefined;
	export let loading: $$Props['loading'] = false;
	export let variant: $$Props['variant'] = 'primary';
	export let builders: $$Props['builders'] = [];
	export { className as class };
</script>

<ButtonPrimitive.Root
	{builders}
	class={cn('piggy-button', `piggy-button--${variant}`, {
		'piggy-button--disabled': $$restProps.disabled,
		className
	})}
	type="button"
	{...$$restProps}
	on:click
	on:keydown
>
	{#if loading}
		<svg
			class="piggy-spinner piggy-animate-spin"
			xmlns="http://www.w3.org/2000/svg"
			fill="none"
			viewBox="0 0 24 24"
		>
			<circle class="piggy-spinner-bg" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
			></circle>
			<path
				class="piggy-spinner-fr"
				fill="currentColor"
				d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
			></path>
		</svg>
	{/if}

	<span>
		<slot />
	</span>
</ButtonPrimitive.Root>

<style>
	:global(.piggy-button) {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		justify-content: center;
		background-color: var(--piggy-color-primary, var(--wp--preset--color--contrast, #007cba));
		color: var(--piggy-color-white, var(--wp--preset--color--base, #fff));
		font-size: var(--piggy-font-size-base, 0.85rem);
		text-decoration: none;
		border: none;
		padding: 0.6rem 1rem;
		border-radius: 5px;
		cursor: pointer;
	}

	:global(.piggy-button--primary) {
		background-color: var(--piggy-color-primary, var(--wp--preset--color--contrast, #007cba));
		color: var(--piggy-color-white, var(--wp--preset--color--base, #fff));
	}

	:global(.piggy-button--secondary) {
		background-color: var(--piggy-color-secondary, var(--wp--preset--color--contrast, #007cba));
		color: var(--piggy-color-white, var(--wp--preset--color--base, #fff));
	}

	:global(.piggy-button--disabled) {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.piggy-spinner {
		width: 0.8rem;
		height: 0.8rem;
		margin-left: -0.25rem;

		color: white;
	}

	.piggy-spinner-bg {
		opacity: 0.25;
	}

	.piggy-spinner-fr {
		opacity: 0.75;
	}
</style>
