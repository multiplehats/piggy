import { api } from "@leat/lib";
import type { GetShopsResponse } from "./types";

export class LeatApiError extends Error {
	status: number;
	statusText: string;
	data: string;
	message: string;

	constructor(status: number, statusText: string, data: string) {
		super(`LeatApiError: ${statusText}`);
		this.status = status;
		this.statusText = statusText;
		this.data = data;
		this.message = data;
	}
}

export class LeatAdminService {
	async getShops() {
		const { data, error } = await api.get<GetShopsResponse>("/leat/private/shops");

		if (error ?? !data) {
			if (error) {
				throw new LeatApiError(error.status, error.statusText, error.data);
			}

			throw new Error("No data returned");
		}

		return data;
	}

	async searchProducts(term: string) {
		const { data, error } = await api.post<
			{
				id: number;
				title: string;
			}[]
		>(`/leat/private/wc-products`, {
			term,
		});

		if (error ?? !data) {
			if (error) {
				throw new LeatApiError(error.status, error.statusText, error.data);
			}

			throw new Error("No data returned");
		}

		return data;
	}

	async getInitialProducts(ids: string | string[] | undefined) {
		if (!ids) {
			return [];
		}

		const idsParam = Array.isArray(ids) ? ids.join(",") : ids;
		const { data, error } = await api.get<
			{
				id: number;
				title: string;
			}[]
		>(`/leat/private/wc-products?ids=${idsParam}`);

		if (error ?? !data) {
			if (error) {
				throw new LeatApiError(error.status, error.statusText, error.data);
			}

			throw new Error("No data returned");
		}

		return data;
	}

	async searchCategories(term: string) {
		const { data, error } = await api.post<
			{
				id: number;
				title: string;
			}[]
		>(`/leat/private/wc-categories`, {
			term,
		});

		if (error ?? !data) {
			if (error) {
				throw new LeatApiError(error.status, error.statusText, error.data);
			}

			throw new Error("No data returned");
		}

		return data;
	}

	async getInitialCategories(ids: string | string[] | undefined) {
		if (!ids) {
			return [];
		}

		const idsParam = Array.isArray(ids) ? ids.join(",") : ids;
		const { data, error } = await api.get<
			{
				id: number;
				title: string;
			}[]
		>(`/leat/private/wc-categories?ids=${idsParam}`);

		if (error ?? !data) {
			if (error) {
				throw new LeatApiError(error.status, error.statusText, error.data);
			}

			throw new Error("No data returned");
		}

		return data;
	}
}

export const service = new LeatAdminService();
