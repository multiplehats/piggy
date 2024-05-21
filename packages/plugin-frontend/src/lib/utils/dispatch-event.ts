const CustomEvent = window.CustomEvent ?? null;

interface DispatchedEventProperties {
	// Whether the event bubbles.
	bubbles?: boolean;
	// Whether the event is cancelable.
	cancelable?: boolean;
	// See https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/detail
	detail?: unknown;
	// Element that dispatches the event. By default, the body.
	element?: Element | null;
}

/**
 * Wrapper function to dispatch an event.
 */
export const dispatchEvent = (
	name: string,
	{ bubbles = false, cancelable = false, element, detail = {} }: DispatchedEventProperties
): void => {
	if (!CustomEvent) {
		return;
	}
	if (!element) {
		element = document.body;
	}
	const event = new CustomEvent(name, {
		bubbles,
		cancelable,
		detail
	});
	element.dispatchEvent(event);
};
