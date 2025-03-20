import { api } from "@leat/lib";
import type {
	GetContactResponse,
	GetCouponsResponse,
	GetEarnRulesResponse,
	GetShopsResponse,
	GetSpendRulesResponse,
	GetTiersResponse,
} from "./types";

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

export class LeatFrontendService {
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

	async getCoupons(userId: number | null) {
		const endpoint = "/leat/v1/coupons";
		const url = userId !== null ? `${endpoint}?userId=${userId}` : endpoint;

		const { data, error } = await api.get<GetCouponsResponse>(url);

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		return data;
	}

	async getContact(userId: number) {
		const endpoint = "/leat/v1/contact";
		const url = `${endpoint}?userId=${userId}`;

		const { data, error } = await api.get<GetContactResponse>(url);

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		return data;
	}

	async getTiers() {
		const { data, error } = await api.get<GetTiersResponse>("/leat/v1/tiers");

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		return data;
	}

	async claimReward(earnRuleId: number, userId: number | null) {
		const { data, error } = await api.post("/leat/v1/earn-reward", {
			userId,
			earnRuleId,
		});

		if (error) {
			if (error) {
				throw new LeatApiError(error.status, error.statusText, error.data);
			}

			throw new Error("No data returned");
		}

		return data;
	}

	async getEarnRules() {
		const { data, error } = await api.get<GetEarnRulesResponse>("/leat/v1/earn-rules");

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		return data;
	}

	async getSpendRules(userId: number | null) {
		const endpoint = "/leat/v1/spend-rules";
		const url = userId !== null ? `${endpoint}?userId=${userId}` : endpoint;

		const { data, error } = await api.get<GetSpendRulesResponse>(url);

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		return data;
	}

	async joinProgram(userId: number | null) {
		const { data, error } = await api.post("/leat/v1/join-program?g", {
			userId,
		});

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		return data;
	}

	async claimSpendRule(spendRuleId: number, userId: number | null) {
		const { data, error } = await api.post(`/leat/v1/spend-rules-claim`, {
			userId,
			id: spendRuleId,
		});

		if (error) {
			if (error) {
				throw new LeatApiError(error.status, error.statusText, error.data);
			}

			throw new Error("No data returned");
		}

		return data;
	}
}

export const apiService = new LeatFrontendService();
