/**
 * Leat Gift Card Integration for WooCommerce Blocks
 *
 * This script adds gift card balance display to the WooCommerce checkout.
 */
import { initGiftCardIntegration } from "./GiftCardBalanceChecker";

// Initialize the integration when the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
	try {
		// Verify required dependencies are available
		if (
			typeof window.wp === "undefined" ||
			typeof window.wc === "undefined" ||
			typeof window.wc.blocksCheckout === "undefined" ||
			(typeof window.wc.blocksCheckout.ExperimentalOrderMeta === "undefined" &&
				typeof window.wc.blocksCheckout.ExperimentalDiscountsMeta === "undefined") ||
			typeof window.wp.plugins === "undefined"
		) {
			console.error("Required WordPress or WooCommerce Blocks components not found");
			return;
		}

		// Check if leatGiftCardConfig is available
		if (typeof window.leatGiftCardConfig === "undefined") {
			console.error("Gift card configuration not found");
			return;
		}

		// Initialize the gift card balance checker component
		initGiftCardIntegration();
		console.log("Gift card integration initialized successfully");
	} catch (error) {
		console.error("Error initializing gift card integration:", error);
	}
});
