<script lang="ts">
	import Gift from "lucide-svelte/icons/gift";
	import Check from "lucide-svelte/icons/check";
	import X from "lucide-svelte/icons/x";
	import type { Coupon } from "@leat/lib";
	import { getSpendRuleLabel, getTranslatedText } from "@leat/i18n";
	import {
		LeatApiError,
		addCartItems,
		applyCoupon,
		getCart,
		removeAllCoupons,
		removeCoupon,
	} from "@leat/lib";
	import { createMutation, createQuery } from "@tanstack/svelte-query";
	import Button from "./button/button.svelte";
	import { creditsName, pluginSettings } from "$lib/modules/settings";
	import { MutationKeys, QueryKeys } from "$lib/utils/query-keys";

	export let coupon: Coupon;

	let couponApplied = false;

	const cartQuery = createQuery({
		queryKey: [QueryKeys.cart],
		queryFn: getCart,
	});

	const applyCouponMutation = createMutation({
		mutationFn: applyCoupon,
		mutationKey: [MutationKeys.applyCoupon, coupon.code],
		onSuccess: () => {
			couponApplied = true;
			// Refetch cart to update the UI
			$cartQuery.refetch();
		},
		onError: () => {
			couponApplied = false;
		},
	});

	const removeAllCouponsMutation = createMutation({
		mutationFn: removeAllCoupons,
	});

	const addCartItemsMutation = createMutation({
		mutationFn: addCartItems,
		onSuccess: () => {
			console.info("[Add Cart Items Success]");
		},
	});

	const removeCouponMutation = createMutation({
		mutationFn: (code: string) => removeCoupon(code),
		onSuccess: () => {
			couponApplied = false;
			// Refetch cart to update the UI
			$cartQuery.refetch();
		},
	});

	$: hasProducts = coupon.rule.selectedProducts?.value?.length > 0;

	// Check if this coupon is already applied in the cart
	$: isAlreadyApplied = $cartQuery.data?.coupons?.some((c) => c.code === coupon.code) ?? false;

	// Set couponApplied state based on cart data or mutation success
	$: couponApplied = isAlreadyApplied || couponApplied;

	function handleApplyCoupon(code: string) {
		// Don't do anything if the coupon is already applied
		if (isAlreadyApplied) return;

		couponApplied = false; // Reset success state on new attempt

		// First check if we need to add products before applying the coupon
		if (hasProducts) {
			$addCartItemsMutation.mutate(
				coupon.rule.selectedProducts.value.map((productId) => ({
					id: Number(productId),
					quantity: 1,
				})),
				{
					onSuccess: () => {
						// Apply the coupon after adding the products
						$applyCouponMutation.mutate(code);
					},
				}
			);
		} else {
			// Check if cart has items and has coupons
			if ($cartQuery.data?.items_count === 0 && $cartQuery.data?.coupons.length > 0) {
				console.info("[Remove All Coupons]");
				// If cart has no items, and has coupons, remove all coupons first.
				$removeAllCouponsMutation.mutate(undefined, {
					onSuccess: () => {
						// Re-apply the original coupon after successful removal
						$applyCouponMutation.mutate(code);
					},
				});
			} else {
				// If cart has items, just apply the coupon directly
				$applyCouponMutation.mutate(code);
			}
		}
	}

	function getErrorMessage(error: unknown): string {
		if (error instanceof LeatApiError) {
			// Prioritize specific message, then data object, then status text
			if (typeof error.data === "string") {
				return error.data || error.statusText || "Error applying coupon";
			}
			return error.statusText || "Error applying coupon";
		}

		if (error instanceof Error) {
			return error.message;
		}
		return "An unknown error occurred";
	}

	// Combine errors for display
	$: combinedError =
		$applyCouponMutation.error ||
		$addCartItemsMutation.error ||
		$removeAllCouponsMutation.error ||
		$removeCouponMutation.error;
</script>

<div class="leat-dashboard-coupon-card">
	<div class="leat-dashboard-coupon-card__icon">
		{#if coupon.rule?.image?.value}
			<img src={coupon.rule.image.value} alt={coupon.code} />
		{:else}
			<Gift size={48} />
		{/if}
	</div>

	<div class="leat-dashboard-coupon-card__info">
		<h4 class="leat-dashboard-coupon-card__header">
			{#if coupon.type === "spend_rule"}
				{getSpendRuleLabel(
					getTranslatedText(coupon.rule.label.value),
					coupon.rule.creditCost.value,
					$creditsName,
					coupon.rule.discountValue?.value,
					coupon.rule.discountType.value
				)}
			{:else if coupon.type === "promotion_rule"}
				{getTranslatedText(coupon.rule.label.value)}
			{/if}
		</h4>

		{#if coupon.type === "spend_rule" && coupon.rule.instructions?.value}
			<p class="leat-dashboard-coupon-card__description">
				{getTranslatedText(coupon.rule.instructions.value)}
			</p>
		{/if}
	</div>

	<div class="leat-dashboard-coupon-card__action">
		{#if combinedError && (!couponApplied || $removeCouponMutation.error)}
			<div class="leat-dashboard-coupon-card__error">
				<!-- eslint-disable-next-line svelte/no-at-html-tags -->
				{@html getErrorMessage(combinedError)}
			</div>
		{/if}

		<div class="leat-dashboard-coupon-card__button-container">
			<div
				class="leat-dashboard-coupon-card__button-wrapper {isAlreadyApplied || couponApplied
					? 'is-applied'
					: ''}"
			>
				<Button
					variant="primary"
					on:click={() => handleApplyCoupon(coupon.code)}
					loading={$applyCouponMutation.isPending ||
						$addCartItemsMutation.isPending ||
						$removeAllCouponsMutation.isPending}
					disabled={isAlreadyApplied ||
						$applyCouponMutation.isPending ||
						$addCartItemsMutation.isPending ||
						$removeAllCouponsMutation.isPending}
					class={couponApplied || isAlreadyApplied
						? "leat-dashboard-coupon-card__button--success"
						: ""}
				>
					{#if couponApplied || isAlreadyApplied}
						<div class="leat-dashboard-coupon-card__button-success">
							<Check size={16} />
							<span class="leat-sr-only">Applied</span>
						</div>
					{:else}
						{getTranslatedText($pluginSettings.dashboard_coupon_cta)}
					{/if}
				</Button>

				{#if isAlreadyApplied || couponApplied}
					<button
						class="leat-dashboard-coupon-card__remove-button"
						on:click={() => $removeCouponMutation.mutate(coupon.code)}
						disabled={$removeCouponMutation.isPending}
						aria-label="Remove coupon"
					>
						<X size={14} />
					</button>
				{/if}
			</div>
		</div>
	</div>
</div>

<style>
	.leat-dashboard-coupon-card {
		position: relative;
		display: grid;
		grid-template-rows: auto auto 1fr;
		background-color: var(--leat-dashboard-card-background-color, #fff);
		padding: 16px;
		text-align: center;
		box-shadow:
			0 0 #0000,
			0 0 #0000,
			0 1px 3px 0 rgb(0 0 0 / 0.1),
			0 1px 2px -1px rgb(0 0 0 / 0.1);
		height: 100%;
		box-sizing: border-box;
		gap: 8px;
	}

	.leat-dashboard-coupon-card__icon {
		display: flex;
		justify-content: center;
		align-items: center;
		height: 80px;
		width: 100%;
	}

	.leat-dashboard-coupon-card__icon img {
		width: auto;
		height: 80px;
		max-width: 100%;
		max-height: 100%;
		object-fit: contain;
		border-radius: 0.375rem;
	}

	.leat-dashboard-coupon-card__info {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	h4.leat-dashboard-coupon-card__header {
		font-size: 1rem;
		margin: 0;
		overflow-wrap: break-word;
		word-break: break-word;
	}

	.leat-dashboard-coupon-card__description {
		font-size: 0.75rem;
		margin: 8px 0 0;
		overflow-wrap: break-word;
		word-break: break-word;
	}

	.leat-dashboard-coupon-card__action {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: flex-end;
		gap: 8px;
		margin-top: auto;
	}

	.leat-dashboard-coupon-card__error {
		color: var(--leat-error-color, #dc2626);
		font-size: 0.75rem;
		text-align: center;
		width: 100%;
	}

	.leat-dashboard-coupon-card__button-container {
		display: flex;
		justify-content: center;
		width: 100%;
	}

	.leat-dashboard-coupon-card__button-wrapper {
		position: relative;
		display: flex;
		max-width: 180px;
	}

	.leat-dashboard-coupon-card__button-wrapper.is-applied {
		display: flex;
		align-items: stretch;
	}

	.leat-dashboard-coupon-card__button-wrapper.is-applied :global(.leat-button) {
		border-top-right-radius: 0;
		border-bottom-right-radius: 0;
		flex: 1;
		margin: 0;
	}

	.leat-dashboard-coupon-card__button-success {
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.leat-dashboard-coupon-card__remove-button {
		display: flex;
		align-items: center;
		justify-content: center;
		background-color: var(--leat-color-primary, var(--wp--preset--color--contrast, #007cba));
		border: 0;
		border-left: 1px solid rgba(255, 255, 255, 0.3);
		color: white;
		cursor: pointer;
		width: 30px;
		padding: 0;
		transition: background-color 0.2s;
		border-top-right-radius: 5px;
		border-bottom-right-radius: 5px;
		margin: 0;
	}

	.leat-dashboard-coupon-card__remove-button:hover {
		background-color: var(--leat-color-primary-dark, #0069a8);
	}

	.leat-dashboard-coupon-card__remove-button:disabled {
		opacity: 0.7;
		cursor: not-allowed;
	}

	.leat-sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border-width: 0;
	}
</style>
