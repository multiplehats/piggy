import { api } from "../client.js";
import { LeatApiError } from "../errors.js";
import type {
	GetContactResponse,
	GetCouponsResponse,
	GetEarnRulesResponse,
	GetGiftcardBalanceResponse,
	GetSpendRulesResponse,
	GetTiersResponse,
} from "./types";

export async function getCoupons(userId: number | null) {
	const endpoint = "/leat/v1/coupons";
	const url = userId !== null ? `${endpoint}?userId=${userId}` : endpoint;

	const { data, error } = await api.get<GetCouponsResponse>(url);

	if (error) {
		throw new LeatApiError(error.status, error.statusText, error.data);
	}

	return data;
}

export async function getContact(userId: number) {
	const endpoint = "/leat/v1/contact";
	const url = `${endpoint}?userId=${userId}`;

	const { data, error } = await api.get<GetContactResponse>(url);

	if (error) {
		throw new LeatApiError(error.status, error.statusText, error.data);
	}

	return data;
}

export async function getTiers() {
	const { data, error } = await api.get<GetTiersResponse>("/leat/v1/tiers");

	if (error) {
		throw new LeatApiError(error.status, error.statusText, error.data);
	}

	return data;
}

export async function claimReward(earnRuleId: number, userId: number | null) {
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

export async function getEarnRules() {
	const { data, error } = await api.get<GetEarnRulesResponse>("/leat/v1/earn-rules");

	if (error) {
		throw new LeatApiError(error.status, error.statusText, error.data);
	}

	return data;
}

export async function getSpendRules(userId: number | null) {
	const endpoint = "/leat/v1/spend-rules";
	const url = userId !== null ? `${endpoint}?userId=${userId}` : endpoint;

	const { data, error } = await api.get<GetSpendRulesResponse>(url);

	if (error) {
		throw new LeatApiError(error.status, error.statusText, error.data);
	}

	return data;
}

export async function joinProgram(userId: number | null) {
	const { data, error } = await api.post("/leat/v1/join-program?g", {
		userId,
	});

	if (error) {
		throw new LeatApiError(error.status, error.statusText, error.data);
	}

	return data;
}

export async function claimSpendRule(spendRuleId: number, userId: number | null) {
	const { data, error } = await api.post(`/leat/v1/spend-rules-claim`, {
		userId,
		id: spendRuleId,
	});

	if (error || !data) {
		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		throw new Error("No data returned");
	}

	return data;
}

export async function getGiftcardBalance(couponCode: string) {
	const { data, error } = await api.post<GetGiftcardBalanceResponse>(`/leat/v1/giftcards`, {
		couponCode,
	});

	if (error || !data) {
		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		throw new Error("No data returned");
	}

	return data;
}
