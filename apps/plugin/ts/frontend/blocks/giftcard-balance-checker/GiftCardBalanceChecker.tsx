import { getGiftcardBalance } from "@leat/lib";
import React, { useEffect, useState } from "react";
import { registerPlugin } from "@wordpress/plugins";
import { getTranslatedText, replaceStrings } from "@leat/i18n";
import "./giftcard-balance-checker.scss";
import type { GetGiftcardBalanceResponse } from "@leat/lib";

type GiftCardBalanceCheckerProps = {
	couponCode?: string;
	cart?: {
		cartCoupons?: Array<{
			code: string;
		}>;
	};
	context?: string;
};

declare global {
	// eslint-disable-next-line ts/consistent-type-definitions
	interface Window {
		wc: {
			blocksCheckout: {
				registerCheckoutFilters: (
					extensionName: string,
					filters: Record<
						string,
						(value: unknown, extensions: unknown, args?: unknown) => unknown
					>
				) => void;
				ExperimentalOrderMeta: React.ComponentType<React.PropsWithChildren<unknown>>;
				ExperimentalDiscountsMeta: React.ComponentType<React.PropsWithChildren<unknown>>;
			};
		};
		jQuery: unknown;

		// Add other relevant window properties if needed
	}
}

enum CheckStatus {
	IDLE = "idle",
	CHECKING = "checking",
	SUCCESS = "success",
	ERROR = "error",
}

type GiftCardResponse = {
	success: boolean;
	data?: GetGiftcardBalanceResponse;
};

async function checkGiftcardBalance(couponCode: string): Promise<GiftCardResponse> {
	try {
		const res = await getGiftcardBalance(couponCode);

		return { success: true, data: res };
	} catch (error) {
		console.error("Error checking gift card balance", error);
		return { success: false };
	}
}

// Gift Card Balance Checker Component
export const GiftCardBalanceChecker: React.FC<GiftCardBalanceCheckerProps> = ({
	couponCode,
	cart,
}) => {
	const [status, setStatus] = useState<CheckStatus>(CheckStatus.IDLE);
	const [balance, setBalance] = useState<string | null>(null);
	const [giftCardBalances, setGiftCardBalances] = useState<Record<string, string>>({});

	const checkBalance = async (code: string): Promise<void> => {
		if (!code || code.length < 9) {
			return;
		}

		setStatus(CheckStatus.CHECKING);
		const response = await checkGiftcardBalance(code);

		if (response.success && response.data?.balance) {
			const balanceValue = response.data?.balance || 0;
			if (balanceValue) {
				setBalance(balanceValue);

				// Add to balances collection
				setGiftCardBalances((prev) => ({
					...prev,
					[code]: balanceValue,
				}));
			}
			setStatus(CheckStatus.SUCCESS);
		} else {
			setStatus(CheckStatus.ERROR);
		}
	};

	// Check balance when the component mounts or when coupon code changes
	useEffect(() => {
		if (couponCode && couponCode.length >= 9) {
			const timer = setTimeout(() => {
				checkBalance(couponCode);
			}, 500);

			return () => clearTimeout(timer);
		} else {
			setStatus(CheckStatus.IDLE);
		}
	}, [couponCode]);

	// Check all coupons in the cart
	useEffect(() => {
		if (!cart?.cartCoupons?.length) {
			// If cart is updated and has no coupons, clear the balances
			if (Object.keys(giftCardBalances).length > 0) {
				setGiftCardBalances({});
				setBalance("");
				setStatus(CheckStatus.IDLE);
			}
			return;
		}

		const checkCoupons = async () => {
			if (!cart.cartCoupons) return;

			// Create a new balances object to track current coupons
			const newBalances: Record<string, string> = {};
			let balancesChanged = false;

			for (const coupon of cart.cartCoupons) {
				if (coupon.code && coupon.code.length >= 9) {
					// If we already have a balance for this code, keep it
					if (giftCardBalances[coupon.code]) {
						newBalances[coupon.code] = giftCardBalances[coupon.code];
					} else {
						// Otherwise check the balance
						await checkBalance(coupon.code);
						// The checkBalance function will update giftCardBalances directly
						balancesChanged = true;
					}
				}
			}

			// Only update if we didn't already update through checkBalance
			// and the available coupons have changed
			if (
				!balancesChanged &&
				Object.keys(newBalances).length !== Object.keys(giftCardBalances).length
			) {
				setGiftCardBalances(newBalances);
			}
		};

		checkCoupons();
	}, [cart?.cartCoupons]);

	// Listen for coupon removal events
	useEffect(() => {
		const handleCouponRemoved = (_event: Event) => {
			// Don't reset all balances immediately
			// We'll rely on the cart prop updates to reflect the current state
			// This provides a more granular update than resetting everything

			// If we don't have cart data, we can check on next cart update
			if (!cart?.cartCoupons?.length) {
				// Only reset if we actually have balances to show and no coupons left
				setGiftCardBalances({});
				setBalance("");
				setStatus(CheckStatus.IDLE);
			}
		};

		// Add event listener for coupon removal
		document.addEventListener("wc-blocks_removed_from_cart", handleCouponRemoved);

		// Clean up
		return () => {
			document.removeEventListener("wc-blocks_removed_from_cart", handleCouponRemoved);
		};
	}, [cart?.cartCoupons]);

	// Add event listener for added_to_cart events to update balances
	useEffect(() => {
		const handleAddedToCart = (_event: Event) => {
			// The cart prop will be updated after this event
			// Just ensure we're in a state that allows showing balances
			if (status === CheckStatus.ERROR) {
				setStatus(CheckStatus.IDLE);
			}
		};

		document.addEventListener("wc-blocks_added_to_cart", handleAddedToCart);

		return () => {
			document.removeEventListener("wc-blocks_added_to_cart", handleAddedToCart);
		};
	}, [status]);

	// Don't render anything if there's no valid gift card applied
	if (Object.keys(giftCardBalances).length === 0 && status === CheckStatus.IDLE) {
		return null;
	}

	// Render balance information for a single coupon being checked
	if (couponCode && status !== CheckStatus.IDLE) {
		let messageContent = null;
		if (status === CheckStatus.CHECKING) {
			messageContent = getTranslatedText(window.leatGiftCardConfig.checkingText);
		} else if (status === CheckStatus.SUCCESS && balance) {
			const successTemplate = getTranslatedText(
				window.leatGiftCardConfig.giftcardAppliedSuccessMessage
			);
			messageContent = replaceStrings(successTemplate, [
				{ "{{code}}": couponCode },
				{ "{{balance}}": balance },
			]);
		}

		// Only render the div if there is content to show
		return messageContent ? (
			<div
				className={`leat-giftcard-balance ${status.toLowerCase()}`}
				// Use dangerouslySetInnerHTML for potential HTML in balance (e.g., wc_price)
				dangerouslySetInnerHTML={{ __html: messageContent }}
			/>
		) : null;
	}

	// Render balances for all gift cards in the cart
	return (
		<div className="leat-giftcard-balances">
			{Object.entries(giftCardBalances).map(([code, balance]) => {
				const successTemplate = getTranslatedText(
					window.leatGiftCardConfig.giftcardAppliedSuccessMessage
				);
				const message = replaceStrings(successTemplate, [
					{ "{{code}}": code },
					{ "{{balance}}": balance },
				]);

				return (
					<div
						key={code}
						className="leat-giftcard-balance success"
						// Use dangerouslySetInnerHTML for potential HTML in balance (e.g., wc_price)
						dangerouslySetInnerHTML={{ __html: message }}
					/>
				);
			})}
		</div>
	);
};

// React component for coupon input that includes balance checking
export const GiftCardCouponInput: React.FC = () => {
	const [couponCode, setCouponCode] = useState<string>("");

	const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>): void => {
		setCouponCode(e.target.value.trim());
	};

	return (
		<div className="gift-card-coupon-input-container">
			<input
				type="text"
				className="wc-block-components-totals-coupon__input"
				value={couponCode}
				onChange={handleInputChange}
				placeholder="Enter gift card code"
			/>
			<GiftCardBalanceChecker couponCode={couponCode} />
		</div>
	);
};

export function initGiftCardIntegration(): void {
	// Try to get the appropriate component (OrderMeta is preferred, DiscountsMeta as fallback)
	const { ExperimentalOrderMeta, ExperimentalDiscountsMeta } = window.wc.blocksCheckout;

	// Determine which component to use
	const SlotComponent = ExperimentalOrderMeta || ExperimentalDiscountsMeta;

	if (!SlotComponent) {
		console.error("WooCommerce Blocks checkout components not found");
		return;
	}

	const render = () => {
		return (
			<SlotComponent>
				<GiftCardBalanceChecker />
			</SlotComponent>
		);
	};

	registerPlugin("leat-giftcard-balance-checker", {
		render,
		scope: "woocommerce-checkout",
	});
}

export default GiftCardBalanceChecker;
