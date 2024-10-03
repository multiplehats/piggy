import Qs from "qs";

export type ApiError = {
	status: number;
	statusText: string;
	data: any;
};

/**
 * A simple wrapper around the WordPress ajax API.
 */
export default function wpApiClient({
	ajaxUrl,
	nonce,
	actionPrefix = "piggy_",
}: {
	ajaxUrl: string;
	nonce: string;
	actionPrefix?: string;
}) {
	const request = async <T = unknown>(
		method: string = "GET",
		action?: string,
		data?: any,
		params?: { [key: string]: any }
	): Promise<{
		data: T | null;
		error: ApiError | null;
	}> => {
		const url = ajaxUrl + (params ? `?${Qs.stringify(params)}` : "");

		return await fetch(url, {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			credentials: "same-origin",
			body: new URLSearchParams(
				Qs.stringify({
					action: actionPrefix + action,
					nonce,
					data,
				})
			).toString(),
		})
			.then(async (response) => {
				if (!response.ok) {
					return {
						data: null,
						error: {
							status: response.status,
							statusText: response.statusText,
							data: await response.json(),
						},
					};
				}

				const payload = await response.json();

				// If payload.success key does not exist, throw error.
				if (payload.success === undefined) {
					throw new Error(
						"Payload does not contain success key, make sure you use wp_send_json_success instead of wp_send_json"
					);
				}

				if (payload.success === false) {
					return {
						data: null,
						error: {
							status: response.status,
							statusText: response.statusText,
							data: payload.data,
						},
					};
				}

				return {
					data: payload.data,
					error: null,
				};
			})
			.catch((error) => {
				return {
					data: null,
					error: {
						status: 500,
						statusText: error.message,
						data: null,
					},
				};
			});
	};

	return {
		get: <T = unknown>(action: string, params?: { [key: string]: any }) => {
			return request<T>("GET", action, undefined, params);
		},
		post: <T = unknown>(action: string, data?: any) => {
			return request<T>("POST", action, data);
		},
		put: <T = unknown>(action: string, data?: any) => {
			return request<T>("PUT", action, data);
		},
		delete: <T = unknown>(action: string) => {
			return request<T>("DELETE", action, undefined);
		},
	};
}
