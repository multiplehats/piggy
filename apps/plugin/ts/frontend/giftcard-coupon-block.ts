// Configuration for gift card checking
type GiftCardConfig = {
	nonce: string;
	ajaxUrl: string;
	checkingText: string;
	balanceText: string;
	errorText: string;
};

// Add a logging utility with level control
const LogLevel = {
	NONE: 0,
	ERROR: 1,
	WARN: 2,
	INFO: 3,
	DEBUG: 4,
};

// Set the current log level (can be changed for production vs development)
const currentLogLevel = LogLevel.ERROR; // Change to ERROR for production, DEBUG for development

function log(level: number, ...args: any[]): void {
	if (level <= currentLogLevel) {
		switch (level) {
			case LogLevel.ERROR:
				console.error(...args);
				break;
			case LogLevel.WARN:
				console.warn(...args);
				break;
			case LogLevel.INFO:
				console.info(...args);
				break;
			case LogLevel.DEBUG:
				console.log(...args);
				break;
		}
	}
}

// Helper function to check gift card balance
async function checkGiftcardBalance(couponCode: string): Promise<any> {
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

// Wait for DOM to be ready
function waitForElement(selector: string, callback: () => void, maxAttempts = 50): boolean {
	let attempts = 0;
	const checkExistence = (): boolean => {
		attempts++;
		if (document.querySelector(selector)) {
			callback();
			return true;
		} else if (attempts < maxAttempts) {
			setTimeout(checkExistence, 100);
			return false;
		}
		console.log(`Element not found after ${maxAttempts} attempts: ${selector}`);
		return false;
	};

	return checkExistence();
}

// Keep track of processed coupon codes to avoid duplicates
const processedCouponCodes = new Set<string>();

// Store coupon balance data to persist across re-renders
const couponBalanceData: Record<string, string> = {};

// Function to process coupon input
function processCouponInput(): void {
	waitForElement(".wc-block-components-totals-coupon__input", () => {
		// Processing for the coupon input field in Blocks checkout
		const couponInputs = document.querySelectorAll(".wc-block-components-totals-coupon__input");

		console.log("Found coupon inputs:", couponInputs.length);

		couponInputs.forEach((input) => {
			// Skip if we've already processed this input
			if ((input as HTMLElement).dataset.leatProcessed) return;
			(input as HTMLElement).dataset.leatProcessed = "true";

			// Create balance display element
			const balanceEl = document.createElement("div");
			balanceEl.className = "leat-giftcard-balance";

			// Find the parent container (the form or a wrapper div)
			const container =
				(input as HTMLElement).closest(".wc-block-components-totals-coupon__content") ||
				input.parentNode;
			container?.appendChild(balanceEl);

			// Add input handler with debounce
			let timer: number;
			input.addEventListener("input", (e: Event) => {
				const target = e.target as HTMLInputElement;
				const couponCode = target.value.trim();
				clearTimeout(timer);

				// Hide balance display if input is empty
				if (couponCode === "") {
					balanceEl.style.display = "none";
					return;
				}

				// Only proceed if coupon code is at least 9 characters (gift card length)
				if (couponCode.length >= 9) {
					// Debounce the check to avoid too many requests
					timer = window.setTimeout(async () => {
						balanceEl.textContent = window.leatGiftCardConfig.checkingText;
						balanceEl.className = "leat-giftcard-balance";
						balanceEl.style.display = "block";

						const response = await checkGiftcardBalance(couponCode);

						if (response.success && response.data.is_giftcard) {
							balanceEl.innerHTML = `${window.leatGiftCardConfig.balanceText} <strong>${response.data.balance}</strong>`;
							balanceEl.className = "leat-giftcard-balance success";
						} else {
							balanceEl.style.display = "none";
						}
					}, 500);
				}
			});
		});

		// Check for applied coupons
		processAppliedCoupons();
	});
}

// Function to process applied coupons
function processAppliedCoupons(): void {
	// Reset processed flag on elements on each check
	document.querySelectorAll("[data-leat-processed]").forEach((el) => {
		delete (el as HTMLElement).dataset.leatProcessed;
	});

	document.querySelectorAll("[data-leat-scanned]").forEach((el) => {
		delete (el as HTMLElement).dataset.leatScanned;
	});

	// All possible selectors for applied coupons in different WooCommerce Blocks versions
	const selectors = [
		// Coupon list items in the cart/checkout totals
		".wc-block-components-totals-discount__coupon-list-item",
		// Coupon chips in newer versions
		".wc-block-components-chip",
		".wc-block-components-chip--radius-small",
		// Applied coupons text
		".wc-block-components-totals-discount .wc-block-components-totals-item__description",
		// Individual coupon items
		".wc-block-components-totals-discount__coupon-list",
		// Discount row in checkout
		".wc-block-components-totals-discount",
		// Cart discount row
		".cart-discount",
	];

	// Check coupon summaries in the totals section
	waitForElement(".wc-block-components-totals-item__description", () => {
		const couponDescriptions = document.querySelectorAll(
			".wc-block-components-totals-discount .wc-block-components-totals-item__description"
		);

		log(LogLevel.DEBUG, "Found coupon descriptions:", couponDescriptions.length);

		couponDescriptions.forEach(async (desc) => {
			// Skip if we've already processed this element
			if ((desc as HTMLElement).dataset.leatProcessed) return;
			(desc as HTMLElement).dataset.leatProcessed = "true";

			const text = desc.textContent || "";
			const couponMatch = text.match(/[A-Z0-9]{9}/i);

			if (couponMatch && couponMatch[0]) {
				const couponCode = couponMatch[0];

				// Skip if this coupon code was already processed
				if (processedCouponCodes.has(couponCode)) {
					log(LogLevel.DEBUG, "Skipping already processed coupon code:", couponCode);
					return;
				}

				const response = await checkGiftcardBalance(couponCode);

				if (response.success && response.data.is_giftcard) {
					// Add to processed list
					processedCouponCodes.add(couponCode);

					// Create balance element as a sibling to the description
					const balanceEl = document.createElement("div");
					balanceEl.className = "leat-giftcard-balance success";
					balanceEl.innerHTML = `<small>${window.leatGiftCardConfig.balanceText}${response.data.balance}</small>`;
					balanceEl.dataset.couponCode = couponCode;
					desc.parentNode?.appendChild(balanceEl);
				}
			}
		});
	});

	// Process all possible coupon elements
	selectors.forEach((selector) => {
		waitForElement(selector, () => {
			const couponItems = document.querySelectorAll(selector);

			log(
				LogLevel.DEBUG,
				`Found coupon items with selector ${selector}:`,
				couponItems.length
			);

			couponItems.forEach(async (item) => {
				// Skip if we've already processed this item
				if ((item as HTMLElement).dataset.leatProcessed) return;
				(item as HTMLElement).dataset.leatProcessed = "true";

				const text = item.textContent || "";
				// Look for a 9-character alphanumeric code that could be a gift card
				const couponMatch = text.match(/[A-Z0-9]{9}/i);

				if (couponMatch && couponMatch[0]) {
					const couponCode = couponMatch[0];
					log(LogLevel.DEBUG, "Found potential gift card coupon:", couponCode);

					// Skip if this coupon code was already processed
					if (processedCouponCodes.has(couponCode)) {
						log(LogLevel.DEBUG, "Skipping already processed coupon code:", couponCode);
						return;
					}

					// Check if a balance element for this coupon already exists nearby
					// Look in this element and up to 3 levels up
					let existingNearby = false;
					let parent: HTMLElement | null = item as HTMLElement;
					for (let i = 0; i < 3; i++) {
						if (!parent) break;
						if (
							parent.querySelector(
								`.leat-giftcard-balance[data-coupon-code='${couponCode}']`
							)
						) {
							existingNearby = true;
							break;
						}
						parent = parent.parentElement;
					}

					if (existingNearby) {
						log(
							LogLevel.DEBUG,
							"Balance already displayed nearby for coupon:",
							couponCode
						);
						return;
					}

					const couponEl = document.createElement("div");
					couponEl.className = "leat-giftcard-balance";
					couponEl.textContent = window.leatGiftCardConfig.checkingText;
					couponEl.dataset.couponCode = couponCode;

					// Add to the item or its parent, depending on the markup
					if (item.classList.contains("wc-block-components-chip")) {
						// For chip-style coupons, add after the chip
						item.parentNode?.insertBefore(couponEl, item.nextSibling);
					} else {
						// For other coupon displays, add inside
						item.appendChild(couponEl);
					}

					const response = await checkGiftcardBalance(couponCode);

					if (response.success && response.data.is_giftcard) {
						// Store the balance data for persistence
						couponBalanceData[couponCode] = response.data.balance;

						// Add to processed list
						processedCouponCodes.add(couponCode);

						couponEl.innerHTML = `<small>${window.leatGiftCardConfig.balanceText}${response.data.balance}</small>`;
						couponEl.className = "leat-giftcard-balance success";
						couponEl.style.display = "block";
					} else {
						couponEl.style.display = "none";
					}
				}
			});
		});
	});

	// Look for all text nodes that might contain coupon codes
	waitForElement(".wp-block-woocommerce-checkout-totals-block", () => {
		const totalsBlock = document.querySelector(".wp-block-woocommerce-checkout-totals-block");
		if (!totalsBlock) return;

		// Helper function to append balance element - moved UP before first use
		const appendBalanceElement = (
			couponCode: string,
			balance: string,
			containerElement: HTMLElement
		): void => {
			const balanceEl = document.createElement("div");
			balanceEl.className = "leat-giftcard-balance success";
			balanceEl.innerHTML = `<small>${window.leatGiftCardConfig.balanceText}${balance}</small>`;
			balanceEl.dataset.couponCode = couponCode;
			containerElement.appendChild(balanceEl);
		};

		// Process a found coupon code
		const processFoundCouponCode = async (
			couponCode: string,
			containerElement: HTMLElement
		): Promise<void> => {
			if (!containerElement || containerElement.dataset.leatProcessed) return;
			containerElement.dataset.leatProcessed = "true";

			log(LogLevel.DEBUG, "Processing found coupon code:", couponCode);

			// Check if a balance element for this coupon already exists nearby in the parent containers
			// Look up to 3 levels up to find existing balance displays
			let parent: HTMLElement | null = containerElement;
			let existingNearby = false;
			for (let i = 0; i < 3; i++) {
				if (!parent) break;

				if (
					parent.querySelector(`.leat-giftcard-balance[data-coupon-code='${couponCode}']`)
				) {
					existingNearby = true;
					break;
				}
				parent = parent.parentElement;
			}

			if (existingNearby) {
				log(LogLevel.DEBUG, "Balance already displayed nearby for coupon:", couponCode);
				return;
			}

			// First check if we already have balance data for this coupon
			if (couponBalanceData[couponCode]) {
				appendBalanceElement(couponCode, couponBalanceData[couponCode], containerElement);
				return;
			}

			const response = await checkGiftcardBalance(couponCode);
			if (response.success && response.data.is_giftcard) {
				// Store the balance data for persistence
				couponBalanceData[couponCode] = response.data.balance;

				// Add to processed list
				processedCouponCodes.add(couponCode);

				appendBalanceElement(couponCode, response.data.balance, containerElement);
			}
		};

		// Find all text nodes in the totals section
		const findCouponCodesInTextNodes = (element: Node): void => {
			if (element.nodeType === 3) {
				// Text node
				const text = element.textContent || "";
				const couponMatch = text.match(/[A-Z0-9]{9}/i);
				if (couponMatch && couponMatch[0]) {
					const couponCode = couponMatch[0];

					// Check if this node's parent already has a balance element
					if (
						element.parentNode &&
						!(element.parentNode as HTMLElement).querySelector(".leat-giftcard-balance")
					) {
						processFoundCouponCode(couponCode, element.parentNode as HTMLElement);
					}
				}
			} else if (element.nodeType === 1) {
				// Element node
				// Skip if we've already processed this element
				if ((element as HTMLElement).dataset.leatScanned) return;
				(element as HTMLElement).dataset.leatScanned = "true";

				// Process child nodes
				for (let i = 0; i < element.childNodes.length; i++) {
					findCouponCodesInTextNodes(element.childNodes[i]);
				}
			}
		};

		findCouponCodesInTextNodes(totalsBlock);
	});

	// Specific targeting for checkout page
	if (document.querySelector(".wp-block-woocommerce-checkout")) {
		const addBalanceToAllDiscountRows = (): void => {
			// Target the discount rows directly
			const discountRows = document.querySelectorAll(".wc-block-components-totals-discount");

			discountRows.forEach((row) => {
				// Skip row if it already has a gift card balance element
				if (row.querySelector(".leat-giftcard-balance")) {
					return;
				}

				// Look for gift card codes in this row
				const rowText = row.textContent || "";
				const matches = rowText.match(/[A-Z0-9]{9}/gi) || [];

				// Only process the first match in each row to avoid duplicates
				if (matches.length > 0) {
					const couponCode = matches[0];

					// Check if this row or any parent already has a balance display
					let existingNearby = false;
					let parent: Element | null = row;
					for (let i = 0; i < 3; i++) {
						if (!parent) break;
						if (
							parent.querySelector(
								`.leat-giftcard-balance[data-coupon-code='${couponCode}']`
							)
						) {
							existingNearby = true;
							break;
						}
						parent = parent.parentElement;
					}

					if (existingNearby) {
						return;
					}

					// If we have balance data for this coupon, add it
					if (couponBalanceData[couponCode]) {
						const descriptionEl =
							row.querySelector(".wc-block-components-totals-item__description") ||
							row;

						const balanceEl = document.createElement("div");
						balanceEl.className = "leat-giftcard-balance success";
						balanceEl.innerHTML = `<small>${window.leatGiftCardConfig.balanceText}${couponBalanceData[couponCode]}</small>`;
						balanceEl.dataset.couponCode = couponCode;
						balanceEl.style.position = "relative";
						balanceEl.style.zIndex = "10";

						descriptionEl.appendChild(balanceEl);
					}
				}
			});
		};

		// Run this more aggressively
		addBalanceToAllDiscountRows();

		// Set a separate interval just for the checkout page
		if (!(window as any).leatCheckoutInterval) {
			(window as any).leatCheckoutInterval = setInterval(() => {
				addBalanceToAllDiscountRows();
			}, 500);

			// Stop after 2 minutes
			setTimeout(() => {
				if ((window as any).leatCheckoutInterval) {
					clearInterval((window as any).leatCheckoutInterval);
					(window as any).leatCheckoutInterval = null;
				}
			}, 120000);
		}
	}
}

// Initialize the gift card coupon functionality
function initGiftCardCoupon(): void {
	log(LogLevel.INFO, "Leat Gift Card Coupon script loaded");
	processCouponInput();
	processAppliedCoupons();

	// Set up an intersection observer to detect when coupon elements enter the viewport
	const intersectionObserver = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					// Use a small delay to let the DOM settle
					setTimeout(() => {
						processCouponInput();
						processAppliedCoupons();
					}, 100);
				}
			});
		},
		{
			root: null, // observe relative to the viewport
			threshold: 0.1, // trigger when at least 10% of the element is visible
		}
	);

	// Observe the main cart/checkout containers
	const containers = [
		".wp-block-woocommerce-checkout",
		".wp-block-woocommerce-cart",
		".woocommerce-checkout",
		".woocommerce-cart-form",
	];

	containers.forEach((selector) => {
		const element = document.querySelector(selector);
		if (element) intersectionObserver.observe(element);
	});

	// Also set up a mutation observer as a fallback for dynamic content
	const mutationObserver = new MutationObserver((mutations) => {
		// Use a small delay to let the DOM settle after changes
		setTimeout(() => {
			processCouponInput();
			processAppliedCoupons();

			// Re-observe any new checkout/cart containers
			containers.forEach((selector) => {
				document.querySelectorAll(selector).forEach((element) => {
					if (element) intersectionObserver.observe(element);
				});
			});
		}, 100);
	});

	// Start observing the document with configured parameters
	mutationObserver.observe(document.body, {
		childList: true,
		subtree: true,
	});

	// Additionally, check for applied coupons every second for the first 15 seconds
	// This is a fallback in case observers miss something
	let checkCount = 0;
	const intervalId = setInterval(() => {
		checkCount++;
		processAppliedCoupons();
		if (checkCount >= 15) {
			clearInterval(intervalId);
		}
	}, 1000);

	// Also check for applied coupons when checkout/cart updates
	if (typeof jQuery === "function") {
		jQuery(document.body).on(
			"updated_checkout updated_cart_totals payment_method_selected updated_shipping_method applied_coupon removed_coupon",
			() => {
				console.log("WooCommerce update event detected, checking gift card balances");
				setTimeout(processAppliedCoupons, 300);
			}
		);
	}

	// Extra check when blocks checkout components update
	try {
		if (wp && wp.data && wp.data.subscribe) {
			wp.data.subscribe(() => {
				// Check if we're on a page with checkout blocks
				if (document.querySelector(".wp-block-woocommerce-checkout")) {
					// Check gift card balances on any store update
					setTimeout(processAppliedCoupons, 300);
				}
			});
		}
	} catch (e) {
		console.error("Failed to subscribe to block store updates:", e);
	}
}

// Run on page load
document.addEventListener("DOMContentLoaded", initGiftCardCoupon);
