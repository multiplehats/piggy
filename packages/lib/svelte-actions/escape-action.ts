import type { ActionReturn } from 'svelte/action';

interface Params {
	callback: () => void;
}

export default function handleEscape(node: HTMLElement, params: Params): ActionReturn<Params> {
	let currentCallback: Params['callback'];

	const handleKeydown = (event: KeyboardEvent): void => {
		if (event.code === 'Escape') {
			currentCallback();
		}
	};

	const toggle = (active: boolean, current_params: Params): void => {
		if (active) {
			currentCallback = current_params.callback;
			node.addEventListener('keydown', handleKeydown, true);
		} else {
			node.removeEventListener('keydown', handleKeydown, true);
		}
	};

	const destroy = (): void => toggle(false, { callback: currentCallback });

	toggle(true, params);

	return {
		destroy,
		update(next_params: Params): void {
			destroy();
			toggle(true, next_params);
		}
	};
}
