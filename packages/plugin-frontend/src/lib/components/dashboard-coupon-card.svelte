<script lang="ts">
	import Check from "lucide-svelte/icons/check";
	import Copy from "lucide-svelte/icons/copy";
	import Gift from "lucide-svelte/icons/gift";
	import { onMount } from "svelte";
	import { getSpendRuleLabel, getTranslatedText } from "$lib/utils/translated-text";
	import { creditsName } from "$lib/modules/settings";
	import type { Coupon } from "$lib/modules/leat/types";

	export let coupon: Coupon;

	$: ({ rule, type } = coupon);

	let isCopied = false;
	let timeoutId: NodeJS.Timeout;
	let isClipboardSupported: boolean;

	$: isClipboardSupported = !!navigator.clipboard && !!navigator.clipboard.writeText;

	onMount(() => {
		return () => {
			if (timeoutId) clearTimeout(timeoutId);
		};
	});

	function copyToClipboard() {
		if (isClipboardSupported) {
			navigator.clipboard
				.writeText(coupon.code)
				.then(() => {
					isCopied = true;
					if (timeoutId) clearTimeout(timeoutId);
					timeoutId = setTimeout(() => {
						isCopied = false;
					}, 3000);
				})
				.catch((err) => console.error("Failed to copy: ", err));
		}
	}
</script>

<div class="leat-dashboard-coupon-card">
	<div class="leat-dashboard-coupon-card__icon">
		{#if coupon.rule?.image?.value}
			<img src={coupon.rule.image.value} alt={coupon.code} />
		{:else}
			<Gift size={48} />
		{/if}
	</div>

	<h4 class="leat-dashboard-coupon-card__header">
		{#if type === "spend_rule"}
			{getSpendRuleLabel(
				getTranslatedText(rule.label.value),
				rule.creditCost.value,
				$creditsName,
				rule.discountValue?.value,
				rule.discountType.value
			)}
		{:else if type === "promotion_rule"}
			{getTranslatedText(rule.label.value)}
		{/if}
	</h4>

	<div class="coupon-input-wrapper">
		<input
			class="coupon-input"
			class:has-copy-button={isClipboardSupported}
			readonly
			value={coupon.code}
		/>

		{#if isClipboardSupported}
			<button class="copy-button" on:click={() => copyToClipboard()}>
				{#if isCopied}
					<Check size={16} />
				{:else}
					<Copy size={16} />
				{/if}
			</button>
		{/if}
	</div>

	<!-- <div class="leat-dashboard-coupon-card__action">
		<Button variant="primary" on:click={() => cartApiService.addCoupon(coupon.code)}>
			Apply coupon
		</Button>
	</div> -->
</div>

<style>
	.leat-dashboard-coupon-card {
		position: relative;
		display: flex;
		flex-direction: column;
		justify-content: flex-start;
		align-items: center;
		background-color: var(--leat-dashboard-card-background-color, #fff);
		padding: 12px;
		text-align: center;
		box-shadow:
			0 0 #0000,
			0 0 #0000,
			0 1px 3px 0 rgb(0 0 0 / 0.1),
			0 1px 2px -1px rgb(0 0 0 / 0.1);
	}

	/* .leat-dashboard-coupon-card__action {
		margin-top: 12px;
	} */

	.coupon-input {
		display: flex;
		align-items: center;
		justify-content: center;
		max-width: 300px;
		height: 1.8rem;
		width: 100%;
		border-radius: 0.375rem;
		border: 1px solid var(--leat-input-border-color, hsl(240 5.9% 90%));
		background-color: var(--leat-input-background-color, #fff);
		padding: 0.5rem 0.75rem;
		font-size: 0.675rem;
		color: var(--leat-input-text-color, #000);
		width: 100%;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		font-family: var(--leat-font-family-mono, monospace);
		box-sizing: border-box;
	}

	.coupon-input.has-copy-button {
		padding-right: 2.5rem;
	}

	.coupon-input:focus-visible {
		outline: none;
		box-shadow: 0 0 0 2px
			var(--leat-input-border-color, var(--wp--preset--color--contrast, #007cba));
	}

	.coupon-input:disabled {
		cursor: not-allowed;
		opacity: 0.5;
	}

	.coupon-input::placeholder {
		color: var(--leat-input-placeholder-color, hsl(240 5.9% 90%));
	}

	.coupon-input::file-selector-button {
		border: 0;
		background-color: transparent;
		font-size: 0.875rem;
		font-weight: 500;
	}

	.leat-dashboard-coupon-card__icon {
		width: 100%;
		display: flex;
		justify-content: center;
		align-items: center;
		margin-bottom: 0.25rem;
	}

	.leat-dashboard-coupon-card__icon img {
		max-width: 100%;
		height: 80px;
		max-height: 100%;
		object-fit: contain;
		border-radius: 0.375rem;
		box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
	}

	h4.leat-dashboard-coupon-card__header {
		font-size: 1rem;
		margin: 0.5rem 0 1rem 0;
	}

	.coupon-input-wrapper {
		position: relative;
		display: inline-block;
		max-width: 200px;
		width: 100%;
	}

	.copy-button {
		position: absolute;
		right: 0.25rem;
		top: 50%;
		z-index: 10;
		transform: translateY(-50%);
		background: none;
		border: none;
		cursor: pointer;
		color: var(--leat-input-text-color, #000);
		opacity: 0.7;
		transition:
			opacity 0.2s,
			color 0.2s;
		padding: 0.25rem;
		display: flex;
		align-items: center;
		justify-content: center;
		outline: none;
	}

	.copy-button:hover {
		opacity: 1;
	}

	.copy-button {
		position: absolute;
		right: 0.25rem;
		top: 50%;
		transform: translateY(-50%);
		background: none;
		border: none;
		cursor: pointer;
		color: var(--leat-input-text-color, #000);
		opacity: 0.7;
		transition: opacity 0.2s;
		padding: 0.25rem;
		display: flex;
		align-items: center;
		justify-content: center;
		outline: none;
	}

	.copy-button:hover {
		opacity: 1;
	}
</style>
