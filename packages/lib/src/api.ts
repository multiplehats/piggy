import type { ApiErrorResponse } from "@leat/types";

export type ApiError = {
	status: number;
	statusText: string;
	data: string;
};

type ApiFetchOptions = {
	headers?: Record<string, string>;
};

// API Wrapper
async function request<T = unknown>(
	method = "GET",
	path: string,
	data?: unknown,
	options?: ApiFetchOptions
): Promise<{
	data: T | null;
	error: ApiError | null;
}> {
	try {
		// Ensure we have the nonce
		const nonce = window?.leatMiddlewareConfig?.wpApiNonce;
		if (!nonce) {
			console.error("WordPress API nonce is missing");
		}

		const response = await fetch(path.startsWith("http") ? path : `/wp-json${path}`, {
			method,
			headers: {
				Accept: "application/json, */*;q=0.1",
				"Cache-Control": "no-cache",
				Pragma: "no-cache",
				"X-WP-Nonce": nonce || "",
				...(method !== "GET" && data ? { "Content-Type": "application/json" } : {}),
				...(options?.headers || {}),
			},
			...(method !== "GET" && data ? { body: JSON.stringify(data) } : {}),
			credentials: "same-origin",
			mode: "same-origin", // Changed from "cors" to "same-origin"
			referrerPolicy: "strict-origin-when-cross-origin",
		});

		if (!response.ok) {
			const errorText = await response.text();
			let errorData;
			try {
				errorData = JSON.parse(errorText);
			} catch {
				errorData = errorText;
			}

			return {
				data: null,
				error: {
					status: response.status,
					statusText: response.statusText,
					data: errorData?.message || errorText,
				},
			};
		}

		const responseData = await response.json();

		// Handle WordPress error responses
		if (responseData?.error_code) {
			return {
				data: null,
				error: {
					status: 200,
					statusText: responseData.error_code,
					data: responseData?.additional_data?.message ?? "Unknown error",
				},
			};
		}

		return {
			data: responseData as T,
			error: null,
		};
	} catch (e) {
		const err = e as ApiErrorResponse | null;
		return {
			data: null,
			error: {
				status: err?.data?.status ? err.data.status : 500,
				statusText: err ? err.code : "unknown",
				data: err ? err.message : "unknown",
			},
		};
	}
}

/**
 * A simple fetch wrapper for making API requests
 */
export const api = {
	get: <T = unknown>(path: string, options?: ApiFetchOptions) => {
		return request<T>("GET", path, undefined, options);
	},
	post: <T = unknown>(path: string, data?: unknown, options?: ApiFetchOptions) => {
		return request<T>("POST", path, data, options);
	},
	put: <T = unknown>(path: string, data?: unknown, options?: ApiFetchOptions) => {
		return request<T>("PUT", path, data, options);
	},
	delete: <T = unknown>(path: string, options?: ApiFetchOptions) => {
		return request<T>("DELETE", path, undefined, options);
	},
};
