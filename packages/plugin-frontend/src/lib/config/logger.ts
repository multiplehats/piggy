import { isDev } from "./environment";

function currentTimeString(): string {
	const now: Date = new Date();
	return `${now.getHours()}:${now.getMinutes()}:${now.getSeconds()}:${now.getMilliseconds()}`;
}

function info(...args: unknown[]): void {
	if (isDev) {
		console.info(`[${currentTimeString()}]`, ...args);
	}
}

function debug(...args: unknown[]): void {
	if (isDev) {
		console.info(`[${currentTimeString()}]`, ...args);
	}
}

function log(...args: unknown[]): void {
	if (isDev) {
		console.info(`[${currentTimeString()}]`, ...args);
	}
}

function warn(...args: unknown[]): void {
	if (isDev) {
		console.warn(`[${currentTimeString()}]`, ...args);
	}
}

function error(...args: unknown[]): void {
	if (isDev) {
		console.error(`[${currentTimeString()}]`, ...args);
	}
}

export const logger = {
	debug,
	info,
	log,
	warn,
	error,
};
