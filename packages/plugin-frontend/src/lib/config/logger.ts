import { isDev } from './environment';

const currentTimeString = (): string => {
	const now: Date = new Date();
	return `${now.getHours()}:${now.getMinutes()}:${now.getSeconds()}:${now.getMilliseconds()}`;
};

const info = (...args: unknown[]): void => {
	if (isDev) {
		console.info(`[${currentTimeString()}]`, ...args);
	}
};

const debug = (...args: unknown[]): void => {
	if (isDev) {
		console.log(`[${currentTimeString()}]`, ...args);
	}
};

const log = (...args: unknown[]): void => {
	if (isDev) {
		console.log(`[${currentTimeString()}]`, ...args);
	}
};

const warn = (...args: unknown[]): void => {
	if (isDev) {
		console.warn(`[${currentTimeString()}]`, ...args);
	}
};

const error = (...args: unknown[]): void => {
	if (isDev) {
		console.error(`[${currentTimeString()}]`, ...args);
	}
};

export const logger = {
	debug,
	info,
	log,
	warn,
	error
};
