import { Action, createHashHistory } from "history";
import type { To, Update } from "history";
import { createHistory } from "svelte-navigator";

function createHashSource() {
	const history = createHashHistory();
	let listeners: ((location: Update) => void)[] = [];

	history.listen((location) => {
		if (history.action === Action.Pop) {
			listeners.forEach((listener) => listener(location));
		}
	});

	return {
		get location() {
			return history.location;
		},
		addEventListener(name: string, handler: Update) {
			if (name !== "popstate") return;
			// @ts-expect-error - This works, just doesn't touch it lol
			listeners.push(handler);
		},
		removeEventListener(name: string, handler: Update) {
			if (name !== "popstate") return;
			// @ts-expect-error - This works, just doesn't touch it lol
			listeners = listeners.filter((fn) => fn !== handler);
		},
		history: {
			get state() {
				return history.location.state;
			},
			pushState(state: unknown, title: string, uri: To) {
				history.push(uri, state);
			},
			replaceState(state: unknown, title: string, uri: To) {
				history.replace(uri, state);
			},
			go(to: number) {
				history.go(to);
			},
		},
	};
}

// @ts-expect-error - Types don't match well.
export const history = createHistory(createHashSource());
