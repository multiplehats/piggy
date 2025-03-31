/**
 * Leat Gift Card Integration for WooCommerce Blocks
 *
 * This script adds gift card balance display to the WooCommerce checkout.
 */
import { initGiftCardIntegration } from "./GiftCardBalanceChecker";

// Initialize the integration when WordPress is ready
function init() {
	try {
		// Verify required dependencies are available
		if (
			typeof window.wc === "undefined" ||
			typeof window.wc.blocksCheckout === "undefined" ||
			(typeof window.wc.blocksCheckout.ExperimentalOrderMeta === "undefined" &&
				typeof window.wc.blocksCheckout.ExperimentalDiscountsMeta === "undefined")
		) {
			console.error("Required WooCommerce Blocks components not found");
			return;
		}

		// Check if leatGiftCardConfig is available
		if (typeof window.leatGiftCardConfig === "undefined") {
			console.error("Gift card configuration not found");
			return;
		}

		initGiftCardIntegration();
	} catch (error) {
		console.error("Error initializing gift card integration:", error);
	}
}

// Try to initialize when DOM is ready
document.addEventListener("DOMContentLoaded", init);

// Also try to initialize when WordPress is ready
if (typeof window.wp !== "undefined" && typeof window.wp.domReady === "function") {
	window.wp.domReady(init);
} else {
	// Fallback to DOMContentLoaded if wp.domReady is not available
	document.addEventListener("DOMContentLoaded", init);
}
