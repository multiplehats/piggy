import { api } from '@piggy/lib';
import type { GetCouponsResponse, GetRewardsResponse, GetShopsResponse } from './types';

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

export class PiggyFrontendService {
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

	async getRewards() {
		const { data, error } = await api.get<GetRewardsResponse>('/piggy/private/rewards');

		if (error ?? !data) {
			if (error) {
				throw new PiggyApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return data;
	}

	async getCoupons(userId?: number) {
		const { data, error } = await api.get<GetCouponsResponse>(`/piggy/v1/coupons?userId=${userId}`);

		if (error) {
			throw new PiggyApiError(error.status, error.statusText, error.data);
		}

		return data;
	}

	async claimReward(earnRuleId: number, userId?: number) {
		const { data, error } = await api.post('/piggy/v1/earn-reward', {
			userId,
			earnRuleId
		});

		if (error) {
			if (error) {
				throw new PiggyApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return data;
	}

	async claimSpendRule(spendRuleId: number, userId?: number) {
		const { data, error } = await api.post(`/piggy/v1/spend-rules-claim`, {
			userId,
			id: spendRuleId
		});

		if (error) {
			if (error) {
				throw new PiggyApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return data;
	}
}

export const apiService = new PiggyFrontendService();
