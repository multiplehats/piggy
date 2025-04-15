import type { ActionReturn } from "svelte/action";

type Params = {
	active: boolean;
	callback: () => void;
	exclude?: string[];
};

export default function clickOutside(node: HTMLElement, params: Params): ActionReturn<Params> {
	let current_callback: Params["callback"];

	const handleClick = (event: Event): void => {
		if (
			params.exclude &&
			params.exclude.some((selector) => (event.target as HTMLElement).matches(selector))
		) {
			return;
		}

		if (!node.contains(event.target as Node)) {
			current_callback();
		}
	};

	const toggle = (current_params: Params): void => {
		const { active, callback } = current_params;

		if (active) {
			current_callback = callback;

			document.addEventListener("click", handleClick, true);
		} else {
			document.removeEventListener("click", handleClick, true);
		}
	};

	toggle(params);

	return {
		update(next_params: Params): void {
			toggle(next_params);
		},
		destroy(): void {
			toggle({ active: false, callback: current_callback });
		},
	};
}
