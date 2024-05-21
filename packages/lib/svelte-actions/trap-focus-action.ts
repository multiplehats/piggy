import type { ActionReturn } from 'svelte/action';

export default function trapFocus(node: HTMLElement): ActionReturn<undefined> {
	function handleKeydown(event: KeyboardEvent): void {
		if (event.code !== 'Tab') {
			return;
		}

		event.preventDefault();

		const tabbables = Array.from(node.querySelectorAll('*')).filter((el) => {
			return (
				'tabIndex' in el &&
				(el.tabIndex as number) >= 0 &&
				!el.hasAttribute('disabled') &&
				!el.hasAttribute('hidden') &&
				!el.getAttribute('aria-hidden')
			);
		});

		if (!tabbables.length) {
			return;
		}

		// Index of element that's currently in focus.
		let index = tabbables.indexOf(node.ownerDocument.activeElement as HTMLElement);

		// The focus is outside. Reset it.
		if (index === -1) {
			index = 0;
		}

		index += tabbables.length + (event.shiftKey ? -1 : 1);
		index %= tabbables.length;

		// @ts-expect-error This is fine.
		tabbables[index].focus();
	}

	function toggleListeners(shouldListen: boolean): void {
		if (shouldListen) {
			node.addEventListener('keydown', handleKeydown);
		} else {
			node.removeEventListener('keydown', handleKeydown);
		}
	}

	toggleListeners(true);

	return {
		destroy(): void {
			toggleListeners(false);
		}
	};
}
