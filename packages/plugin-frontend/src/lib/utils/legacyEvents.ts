import type { AddToCartEventDetail } from '@piggy/types';
import { dispatchEvent } from './dispatch-event';

let fragmentRequestTimeoutId: ReturnType<typeof setTimeout>;

// This is a hack to trigger cart updates till we migrate to block based cart
// that relies on the store, see
// https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1247
export const triggerFragmentRefresh = (): void => {
	if (fragmentRequestTimeoutId) {
		clearTimeout(fragmentRequestTimeoutId);
	}
	fragmentRequestTimeoutId = setTimeout(() => {
		dispatchEvent('wc_fragment_refresh', {
			bubbles: true,
			cancelable: true
		});
	}, 50);
};

export const triggerAddingToCartEvent = (): void => {
	dispatchEvent('piggy_adding_to_cart', {
		bubbles: true,
		cancelable: true
	});
};

export const triggerAddedToCartEvent = ({
	preserveCartData = false
}: AddToCartEventDetail): void => {
	dispatchEvent('piggy_added_to_cart', {
		bubbles: true,
		cancelable: true,
		detail: { preserveCartData }
	});
};

/**
 * Function that listens to a jQuery event and dispatches a native JS event.
 * Useful to convert WC Core events into events that can be read by blocks.
 *
 * Returns a function to remove the jQuery event handler. Ideally it should be
 * used when the component is unmounted.
 */
export const translateJQueryEventToNative = (
	// Name of the jQuery event to listen to.
	jQueryEventName: string,
	// Name of the native event to dispatch.
	nativeEventName: string,
	// Whether the event bubbles.
	bubbles = false,
	// Whether the event is cancelable.
	cancelable = false
): (() => void) => {
	if (typeof jQuery !== 'function') {
		return () => void null;
	}

	const eventDispatcher = () => {
		dispatchEvent(nativeEventName, { bubbles, cancelable });
	};

	jQuery(document).on(jQueryEventName, eventDispatcher);

	return () => jQuery(document).off(jQueryEventName, eventDispatcher);
};
