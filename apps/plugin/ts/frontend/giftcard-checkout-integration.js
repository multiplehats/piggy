/**
 * Leat Gift Card Integration for WooCommerce Blocks
 *
 * This script adds gift card balance display to the WooCommerce checkout.
 */

/**
 * Helper function to check gift card balance
 */
async function checkGiftcardBalance(couponCode) {
	try {
		const formData = new FormData();
		formData.append("action", "leat_check_giftcard_balance");
		formData.append("coupon_code", couponCode);

		if (window.leatGiftCardConfig && window.leatGiftCardConfig.nonce) {
			formData.append("nonce", window.leatGiftCardConfig.nonce);
		}

		const response = await fetch(
			window.leatGiftCardConfig
				? window.leatGiftCardConfig.ajaxUrl
				: "/wp-admin/admin-ajax.php",
			{
				method: "POST",
				body: formData,
			}
		);

		return await response.json();
	} catch (error) {
		console.error("Error checking gift card balance", error);
		return { success: false };
	}
}

/**
 * Initialize the gift card integration
 */
document.addEventListener("DOMContentLoaded", () => {
	// Make sure WooCommerce Blocks and WordPress components are available
	if (
		typeof window.wp === "undefined" ||
		typeof window.wc === "undefined" ||
		typeof window.wc.blocksCheckout === "undefined" ||
		typeof window.wc.blocksCheckout.ExperimentalDiscountsMeta === "undefined" ||
		typeof window.wp.element === "undefined" ||
		typeof window.wp.plugins === "undefined" ||
		typeof window.wp.plugins.registerPlugin === "undefined"
	) {
		console.log("Required WordPress or WooCommerce Blocks components not found");
		return;
	}

	const { ExperimentalDiscountsMeta } = window.wc.blocksCheckout;
	const { registerPlugin } = window.wp.plugins;
	const { createElement, useState, useEffect } = window.wp.element;

	// Define the component that will check for gift card balances
	function GiftCardBalanceChecker(props) {
		const [giftCardBalances, setGiftCardBalances] = useState({});
		const [isLoading, setIsLoading] = useState(false);
		const cart = props.cart || {};

		// Check for gift cards when cart coupons change
		useEffect(() => {
			if (!cart.coupons || !cart.coupons.length) return;

			async function checkCoupons() {
				setIsLoading(true);
				const balances = {};

				// Only check coupons that look like gift cards (9 character code)
				const potentialGiftCards = cart.coupons.filter(
					(coupon) => coupon.code && coupon.code.length === 9
				);

				for (const coupon of potentialGiftCards) {
					const response = await checkGiftcardBalance(coupon.code);
					if (response.success && response.data && response.data.is_giftcard) {
						balances[coupon.code] = response.data.balance;
					}
				}

				setGiftCardBalances(balances);
				setIsLoading(false);
			}

			checkCoupons();
		}, [cart.coupons]);

		// If no gift cards or still loading initial check, don't render anything
		if (Object.keys(giftCardBalances).length === 0 && !isLoading) {
			return null;
		}

		const config = window.leatGiftCardConfig || {
			checkingText: "Checking gift card balance...",
			balanceText: "Gift card balance: ",
		};

		return createElement(
			"div",
			{ className: "leat-giftcard-balance-checker" },
			isLoading
				? createElement("div", { className: "leat-giftcard-loading" }, config.checkingText)
				: Object.entries(giftCardBalances).map(([code, balance]) =>
						createElement(
							"div",
							{
								key: code,
								className: "leat-giftcard-balance success",
							},
							createElement("strong", {}, `${code}: `),
							config.balanceText + balance
						)
					)
		);
	}

	// Define the render function for our plugin
	const render = function () {
		return createElement(ExperimentalDiscountsMeta, {}, (props) => {
			return createElement(GiftCardBalanceChecker, props);
		});
	};

	// Register plugins for both checkout and cart contexts
	registerPlugin("leat-giftcard-balance-checker", {
		render,
		scope: "woocommerce-checkout",
	});

	registerPlugin("leat-giftcard-balance-checker-cart", {
		render,
		scope: "woocommerce-cart",
	});

	console.log("Leat gift card integration initialized with WooCommerce Blocks.");
});
