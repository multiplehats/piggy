import apiFetch from "@wordpress/api-fetch";
import type { APIFetchMiddleware, APIFetchOptions } from "@wordpress/api-fetch";
import type { ApiErrorResponse } from "@leat/types";
import { LocalStorageKeys } from "../config/constants";
import { logger } from "$lib/config/logger";

// Stores the current nonce for the middleware.
let currentNonce = "";
let currentTimestamp = 0;

try {
	const storedNonceValue = window.localStorage.getItem(LocalStorageKeys.storeApiNonce);
	const parsedNonce = storedNonceValue
		? (JSON.parse(storedNonceValue) as { nonce: string; timestamp: number })
		: null;
	currentNonce = parsedNonce?.nonce || "";
	currentTimestamp = parsedNonce?.timestamp || 0;
} catch {
	// We can ignore an error from JSON parse.
}

/**
 * Returns whether or not this is a wc/store API request.
 */
function isStoreApiRequest(options: APIFetchOptions) {
	const url = options.url || options.path;

	if (!url) {
		return false;
	}

	if (!options.method || options.method === "GET") {
		return false;
	}

	return /wc\/store\/v1\//.exec(url) !== null;
}

/**
 * Set the current nonce from a header object.
 */
function setNonce(headers: APIFetchOptions["headers"] | Headers) {
	let nonce: string | null = null;
	let timestamp: string | null = null;

	if (headers instanceof Headers) {
		nonce = headers.get("Nonce");
		timestamp = headers.get("Nonce-Timestamp");
	} else if (typeof headers === "object" && headers !== null) {
		nonce = headers.Nonce;
		timestamp = headers["Nonce-Timestamp"];
	}

	if (nonce) {
		updateNonce(nonce, timestamp);
	}
}

/**
 * Updates the stored nonce within localStorage so it is persisted between page loads.
 */
function updateNonce(nonce: string, timestamp: string | null) {
	// If the "new" nonce matches the current nonce, we don't need to update.
	if (nonce === currentNonce) {
		return;
	}

	// Only update the nonce if newer. It might be coming from cache.
	if (currentTimestamp && timestamp && Number(timestamp) < currentTimestamp) {
		return;
	}

	currentNonce = nonce;
	currentTimestamp = Number(timestamp) || Date.now() / 1000; // Convert ms to seconds to match php time()

	// Update the persisted values.
	window.localStorage.setItem(
		LocalStorageKeys.storeApiNonce,
		JSON.stringify({
			nonce: currentNonce,
			timestamp: currentTimestamp,
		})
	);
}

function appendNonceHeader(request: APIFetchOptions) {
	const headers = request.headers || {};

	request.headers = {
		...headers,
		Nonce: currentNonce,
	};

	return request;
}

const wcStoreApiNonceMiddleware: APIFetchMiddleware = (options, next) => {
	if (isStoreApiRequest(options)) {
		options = appendNonceHeader(options);

		if (Array.isArray(options?.data?.requests)) {
			options.data.requests = options.data.requests.map(appendNonceHeader);
		}
	}

	return next(options);
};

const logMiddleware: APIFetchMiddleware = (options, next) => {
	const start = Date.now();
	const result = next(options);

	result
		.then(() => {
			logger.info(`[api] ${options.path} ${Date.now() - start}ms`);
		})
		.catch((e) => {
			logger.error(`[api] ${options.path} ${Date.now() - start}ms`, e);
		});

	return result;
};

apiFetch.use(wcStoreApiNonceMiddleware);
apiFetch.use(logMiddleware);

// @see https://github.com/woocommerce/woocommerce-blocks/blob/6cd3ab745be5a07ef486eee9f4f34c1708428154/assets/js/middleware/store-api-nonce.js#L114C1-L114C30
// @ts-expect-error - This is actually available, but not typed in the package.
apiFetch.setNonce = setNonce;

updateNonce(
	window.leatMiddlewareConfig.storeApiNonce,
	window.leatMiddlewareConfig.wcStoreApiNonceTimestamp
);

export type ApiError = {
	status: number;
	statusText: string;
	data: string;
};

// API Wrapper
async function request<T = unknown>(
	method = "GET",
	path: string,
	data?: unknown,
	options?: APIFetchOptions
): Promise<{
	data: T | null;
	error: ApiError | null;
}> {
	return apiFetch<T>({ path, method, data, ...options })
		.then((data) => {
			return {
				data,
				error: null,
			};
		})
		.catch((e) => {
			const err = e as {
				data: {
					status: number;
					code: string;
					message: string;
				};
				status: number;
				code: string;
				message: string;
			} | null;

			return {
				data: null,
				error: {
					status: err?.data?.status ? err.data.status : 500,
					statusText: err ? err.code : "unknown",
					data: err ? err.message : "unknown",
				},
			};
		});
}

/**
 * A simple SDK wrapper around the `@wordpress/api-fetch` package.
 *
 * @see https://github.com/woocommerce/woocommerce-blocks/blob/trunk/src/StoreApi/docs
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch
 */
export const api = {
	get: <T = unknown>(path: string, options?: APIFetchOptions) => {
		return request<T>("GET", path, undefined, options);
	},
	post: <T = unknown>(path: string, data?: unknown, options?: APIFetchOptions) => {
		return request<T>("POST", path, data, options);
	},
	put: <T = unknown>(path: string, data?: unknown, options?: APIFetchOptions) => {
		return request<T>("PUT", path, data, options);
	},
	delete: <T = unknown>(path: string, options?: APIFetchOptions) => {
		return request<T>("DELETE", path, undefined, options);
	},
};
