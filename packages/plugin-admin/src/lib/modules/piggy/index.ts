import { api } from '@piggy/lib';
import type {
	AdminGetApiKeyResponse,
	AdminSetApiKeyParams,
	AdminSetApiKeyResponse,
	GetShopsResponse
} from './types';

export class PiggyApiError extends Error {
	status: number;
	statusText: string;
	data: string;
	message: string;

	constructor(status: number, statusText: string, data: string) {
		super(`PiggyApiError: ${statusText}`);
		this.status = status;
		this.statusText = statusText;
		this.data = data;
		this.message = data;
	}
}

export class PiggyService {
	async getApiKey() {
		const { data, error } = await api.get<AdminGetApiKeyResponse>('/piggy/private/api-key', {
			cache: 'no-store'
		});

		if (error ?? !data) {
			if (error) {
				throw new PiggyApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return data;
	}

	async setApiKey(params: AdminSetApiKeyParams) {
		const { data, error } = await api.post<AdminSetApiKeyResponse>(
			'/piggy/private/api-key',
			params
		);

		console.log(data, error);

		if (error ?? !data) {
			if (error) {
				throw new PiggyApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return data;
	}

	async getShops() {
		const { data, error } = await api.get<GetShopsResponse>('/piggy/private/shops');

		if (error ?? !data) {
			if (error) {
				throw new PiggyApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return data;
	}
}
