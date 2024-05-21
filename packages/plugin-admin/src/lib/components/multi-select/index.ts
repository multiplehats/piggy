export { default } from './multi-select.svelte';

export type Option = string | number | ObjectOption;

export interface ObjectOption {
	label: string | number; // user-displayed text
	value?: unknown; // associated value, can be anything incl. objects (defaults to label if undefined)
	title?: string; // on-hover tooltip
	disabled?: boolean; // make this option unselectable
	preselected?: boolean; // make this option selected on page load (before any user interaction)
	disabledTitle?: string; // override the default disabledTitle = 'This option is disabled'
	selectedTitle?: string; // tooltip to display when this option is selected and hovered
	[key: string]: unknown; // allow any other keys users might want
}

export interface DispatchEvents {
	add: { option: Option };
	remove: { option: Option };
	removeAll: { options: Option[] };
	search: { query: string };
	change: {
		option?: Option;
		options?: Option[];
		type: 'add' | 'remove' | 'removeAll';
	};
	open: { event: Event };
	close: { event: Event };
}

export type MultiSelectEvents = {
	[key in keyof DispatchEvents]: CustomEvent<DispatchEvents[key]>;
} & {
	blur: FocusEvent;
	click: MouseEvent;
	focus: FocusEvent;
	keydown: KeyboardEvent;
	keyup: KeyboardEvent;
	mouseenter: MouseEvent;
	mouseleave: MouseEvent;
	touchcancel: TouchEvent;
	touchend: TouchEvent;
	touchmove: TouchEvent;
	touchstart: TouchEvent;
};

// Firefox lacks support for scrollIntoViewIfNeeded, see
// https://github.com/janosh/svelte-multiselect/issues/87
// this polyfill was copied from
// https://github.com/nuxodin/lazyfill/blob/a8e63/polyfills/Element/prototype/scrollIntoViewIfNeeded.js
if (
	typeof Element !== `undefined` &&
	// @ts-expect-error - TODO: Figure this out
	!Element.prototype?.scrollIntoViewIfNeeded &&
	typeof IntersectionObserver !== `undefined`
) {
	// @ts-expect-error - TODO: Figure this out
	Element.prototype.scrollIntoViewIfNeeded = function (centerIfNeeded = true) {
		const el = this as HTMLElement;
		new IntersectionObserver(function ([entry]) {
			const ratio = entry.intersectionRatio;
			if (ratio < 1) {
				const place = ratio <= 0 && centerIfNeeded ? `center` : `nearest`;
				el.scrollIntoView({
					block: place,
					inline: place
				});
			}
			// @ts-expect-error - TODO: Figure this out
			// eslint-disable-next-line
			this.disconnect();
		}).observe(this);
	};
}
