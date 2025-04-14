import { dispatchEvent } from "./dispatch-event";

let fragmentRequestTimeoutId: ReturnType<typeof setTimeout>;

// This is a hack to trigger cart updates till we migrate to block based cart
// that relies on the store, see
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1247
export function triggerFragmentRefresh(): void {
	console.info("[legacyEvents] triggerFragmentRefresh");
	if (fragmentRequestTimeoutId) {
		clearTimeout(fragmentRequestTimeoutId);
	}
	fragmentRequestTimeoutId = setTimeout(() => {
		console.info("[legacyEvents] triggerFragmentRefresh");

		// Trigger legacy WooCommerce cart fragment refresh
		dispatchEvent("wc_fragment_refresh", {
			bubbles: true,
			cancelable: true,
		});

		// Also trigger the updated_wc_div event for block-based carts
		dispatchEvent("updated_wc_div", {
			bubbles: true,
			cancelable: true,
		});
	}, 50);
}

export function triggerAddingToCartEvent(): void {
	dispatchEvent("leat_adding_to_cart", {
		bubbles: true,
		cancelable: true,
	});
}

export function triggerAddedToCartEvent({ preserveCartData = false }): void {
	dispatchEvent("leat_added_to_cart", {
		bubbles: true,
		cancelable: true,
		detail: { preserveCartData },
	});
}

/**
 * Function that listens to a jQuery event and dispatches a native JS event.
 * Useful to convert WC Core events into events that can be read by blocks.
 *
 * Returns a function to remove the jQuery event handler. Ideally it should be
 * used when the component is unmounted.
 */
export function translateJQueryEventToNative(
	jQueryEventName: string,
	// Name of the native event to dispatch.
	nativeEventName: string,
	// Whether the event bubbles.
	bubbles = false,
	// Whether the event is cancelable.
	cancelable = false
): () => void {
	if (typeof jQuery !== "function") {
		return () => void null;
	}

	const eventDispatcher = () => {
		dispatchEvent(nativeEventName, { bubbles, cancelable });
	};

	jQuery(document).on(jQueryEventName, eventDispatcher);

	return () => jQuery(document).off(jQueryEventName, eventDispatcher);
}
