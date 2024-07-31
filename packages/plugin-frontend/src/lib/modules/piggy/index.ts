import { api } from '@piggy/lib';
import type { EarnRuleType } from '@piggy/types/plugin/settings/adminTypes';
import type { GetRewardsResponse, GetShopsResponse } from './types';

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

	claimReward(type: EarnRuleType) {
		console.log('Claiming reward', type);
	}
}
