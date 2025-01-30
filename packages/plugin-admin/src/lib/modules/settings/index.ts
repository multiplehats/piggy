import { get } from "svelte/store";
import { api } from "@leat/lib";
import type { PluginOptionsAdminKeys } from "@leat/types";
import type {
	GetEarnRuleByIdParams,
	GetEarnRuleByIdResponse,
	GetEarnRulesResponse,
	GetPromotionRuleByIdParams,
	GetPromotionRuleByIdResponse,
	GetPromotionRulesResponse,
	GetSettingByIdParams,
	GetSettingByIdResponse,
	GetSettingsResponse,
	GetSpendRuleByIdParams,
	GetSpendRuleByIdResponse,
	GetSpendRulesResponse,
	GetSyncVouchersInformationParams,
	GetSyncVouchersInformationResponse,
	SaveSettingsParams,
	SaveSettingsResponse,
	TaskInformation,
	UpsertEarnRuleParams,
	UpsertEarnRuleResponse,
	UpsertPromotionRuleParams,
	UpsertPromotionRuleResponse,
	UpsertSpendRuleParams,
	UpsertSpendRuleResponse,
} from "./types";

export class SettingsAdminApiError extends Error {
	status: number;
	statusText: string;
	data: string;
	message: string;

	constructor(status: number, statusText: string, data: string) {
		super(`SettingsAdminApiError: ${statusText}`);
		this.status = status;
		this.statusText = statusText;
		this.data = data;
		this.message = data;
	}
}

export class SettingsAdminService {
	async saveSettings(settingsStore: SaveSettingsParams): Promise<SaveSettingsResponse> {
		const { data, error } = await api.post<SaveSettingsResponse>("/leat/private/settings", {
			settings: Object.entries(get(settingsStore)).reduce(
				(acc, [key, setting]) => {
					acc[key] = {
						id: setting.id,
						type: setting.type,
						value: setting.value,
					};
					return acc;
				},
				{} as Record<
					string,
					{
						id: string;
						type: string;
						value: unknown;
					}
				>
			),
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getAllSettings(): Promise<GetSettingsResponse> {
		const { data, error } = await api.get<GetSettingsResponse>("/leat/private/settings", {
			cache: "no-store",
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getSetting<K extends PluginOptionsAdminKeys>({
		id,
	}: GetSettingByIdParams<K>): Promise<GetSettingByIdResponse<K>> {
		const { data, error } = await api.get<GetSettingByIdResponse<K>>(
			`/leat/private/settings/?id=${id}`,
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getEarnRuleById({ id }: GetEarnRuleByIdParams): Promise<GetEarnRuleByIdResponse> {
		const { data, error } = await api.get<GetEarnRuleByIdResponse>(
			`/leat/v1/earn-rules/?id=${id}&status=publish,draft`,
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getEarnRules(): Promise<GetEarnRulesResponse> {
		const { data, error } = await api.get<GetEarnRulesResponse>(
			"/leat/v1/earn-rules?status=draft,publish",
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async upsertEarnRule(earnRule: UpsertEarnRuleParams): Promise<UpsertEarnRuleResponse> {
		const { data, error } = await api.post<UpsertEarnRuleResponse>(
			"/leat/v1/earn-rules",
			earnRule
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getSpendRules(): Promise<GetSpendRulesResponse> {
		const { data, error } = await api.get<GetSpendRulesResponse>(
			"/leat/v1/spend-rules?status=draft,publish",
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async upsertSpendRule(spendRule: UpsertSpendRuleParams): Promise<UpsertSpendRuleResponse> {
		const { data, error } = await api.post<UpsertSpendRuleResponse>(
			"/leat/v1/spend-rules",
			spendRule
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async syncRewards(): Promise<{ ok: true }> {
		const { data, error } = await api.get<{ ok: true }>(`/leat/private/spend-rules-sync`, {
			cache: "no-store",
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getSpendRuleById({ id }: GetSpendRuleByIdParams): Promise<GetSpendRuleByIdResponse> {
		const { data, error } = await api.get<GetSpendRuleByIdResponse>(
			`/leat/v1/spend-rules/?id=${id}&status=publish,draft`,
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async syncPromotions() {
		const { data, error } = await api.post<{ success: true }>(`/leat/private/sync-promotions`, {
			cache: "no-store",
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getSyncPromotionsInformation() {
		const { data, error } = await api.get<TaskInformation>(`/leat/private/sync-promotions`, {
			cache: "no-store",
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async syncVouchers(id: string): Promise<{ ok: true }> {
		const { data, error } = await api.post<{ ok: true }>(
			`/leat/private/sync-vouchers`,
			{
				id,
			},
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getSyncVouchersInformation({
		id,
	}: GetSyncVouchersInformationParams): Promise<GetSyncVouchersInformationResponse> {
		const { data, error } = await api.get<GetSyncVouchersInformationResponse>(
			`/leat/private/sync-vouchers?id=${id}`,
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getPromotionRules(): Promise<GetPromotionRulesResponse> {
		const { data, error } = await api.get<GetPromotionRulesResponse>(
			"/leat/v1/promotion-rules?status=draft,publish",
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async getPromotionRuleById({
		id,
	}: GetPromotionRuleByIdParams): Promise<GetPromotionRuleByIdResponse> {
		const { data, error } = await api.get<GetPromotionRuleByIdResponse>(
			`/leat/v1/promotion-rules/?id=${id}&status=publish,draft`,
			{
				cache: "no-store",
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}

	async upsertPromotionRule(
		promotionRule: UpsertPromotionRuleParams
	): Promise<UpsertPromotionRuleResponse> {
		const { data, error } = await api.post<UpsertPromotionRuleResponse>(
			"/leat/v1/promotion-rules",
			promotionRule
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, "No data returned", "No data returned");
		}

		return data;
	}
}
