import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
import { registerPlugin } from "@wordpress/plugins";

type GiftCardBalanceCheckerProps = {
	couponCode?: string;
	cart?: {
		coupons?: Array<{
			code: string;
		}>;
		cartCoupons?: Array<{
			code: string;
		}>;
	};
	context?: string;
};

type GiftCardConfig = {
	nonce: string;
	ajaxUrl: string;
	checkingText: string;
	balanceText: string;
	errorText: string;
};

// Add typings for window object
declare global {
	// eslint-disable-next-line ts/consistent-type-definitions
	interface Window {
		leatGiftCardConfig: GiftCardConfig;
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
	}
}

// Status enum for the balance checking process
enum CheckStatus {
	IDLE = "idle",
	CHECKING = "checking",
	SUCCESS = "success",
	ERROR = "error",
}

type GiftCardResponse = {
	success: boolean;
	data?: {
		is_giftcard: boolean;
		balance: string;
	};
};

// Function to check gift card balance (same as original)
async function checkGiftcardBalance(couponCode: string): Promise<GiftCardResponse> {
	try {
		const formData = new FormData();
		formData.append("action", "leat_check_giftcard_balance");
		formData.append("coupon_code", couponCode);
		formData.append("nonce", window.leatGiftCardConfig.nonce);

		const response = await fetch(window.leatGiftCardConfig.ajaxUrl, {
			method: "POST",
			body: formData,
		});

		const data = await response.json();
		return data;
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
	const [balance, setBalance] = useState<string>("");
	const [giftCardBalances, setGiftCardBalances] = useState<Record<string, string>>({});

	// Function to check the balance
	const checkBalance = async (code: string): Promise<void> => {
		if (!code || code.length < 9) {
			return;
		}

		setStatus(CheckStatus.CHECKING);
		const response = await checkGiftcardBalance(code);

		if (response.success && response.data?.is_giftcard) {
			const balanceValue = response.data?.balance || "";
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
		console.log("couponCode", couponCode);
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
		if (!cart?.cartCoupons?.length) return;

		const checkCoupons = async () => {
			if (!cart.cartCoupons) return;

			for (const coupon of cart.cartCoupons) {
				if (coupon.code && coupon.code.length >= 9) {
					await checkBalance(coupon.code);
				}
			}
		};

		checkCoupons();
	}, [cart?.cartCoupons]);

	// Don't render anything if there's no valid gift card applied
	if (Object.keys(giftCardBalances).length === 0 && status === CheckStatus.IDLE) {
		return null;
	}

	// Render balance information for a single coupon being checked
	if (couponCode && status !== CheckStatus.IDLE) {
		return (
			<div
				className={`leat-giftcard-balance ${status === CheckStatus.SUCCESS ? "success" : ""}`}
			>
				{status === CheckStatus.CHECKING && window.leatGiftCardConfig.checkingText}
				{status === CheckStatus.SUCCESS && (
					<>
						{window.leatGiftCardConfig.balanceText} <strong>{balance}</strong>
					</>
				)}
			</div>
		);
	}

	// Render balances for all gift cards in the cart
	return (
		<div className="leat-giftcard-balances">
			{Object.entries(giftCardBalances).map(([code, balance]) => (
				<div key={code} className="leat-giftcard-balance success">
					<strong>{code}:</strong> {window.leatGiftCardConfig.balanceText}{" "}
					<span dangerouslySetInnerHTML={{ __html: balance }} />
				</div>
			))}
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

// Initialize WooCommerce Blocks integration
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

	// Register the plugin for both cart and checkout contexts
	registerPlugin("leat-giftcard-balance-checker", {
		render,
		scope: "woocommerce-checkout",
	});

	registerPlugin("leat-giftcard-balance-checker-cart", {
		render,
		scope: "woocommerce-cart",
	});
}

export default GiftCardBalanceChecker;
