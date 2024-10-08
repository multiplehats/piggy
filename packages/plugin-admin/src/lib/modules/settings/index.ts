import { get } from "svelte/store";
import { api } from "@piggy/lib";
import type { PluginOptionsAdminKeys } from "@piggy/types";
import type {
	GetEarnRuleByIdParams,
	GetEarnRuleByIdResponse,
	GetEarnRulesResponse,
	GetSettingByIdParams,
	GetSettingByIdResponse,
	GetSettingsResponse,
	GetSpendRuleByIdParams,
	GetSpendRuleByIdResponse,
	GetSpendRulesResponse,
	SaveSettingsParams,
	SaveSettingsResponse,
	UpsertEarnRuleParams,
	UpsertEarnRuleResponse,
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
		const { data, error } = await api.post<SaveSettingsResponse>("/piggy/private/settings", {
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
		const { data, error } = await api.get<GetSettingsResponse>("/piggy/private/settings", {
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
			`/piggy/private/settings/?id=${id}`,
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
			`/piggy/v1/earn-rules/?id=${id}&status=publish,draft`,
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
			"/piggy/v1/earn-rules?status=draft,publish",
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
			"/piggy/v1/earn-rules",
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
			"/piggy/v1/spend-rules?status=draft,publish",
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
			"/piggy/v1/spend-rules",
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
		const { data, error } = await api.get<{ ok: true }>(`/piggy/v1/spend-rules-sync`, {
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
			`/piggy/v1/spend-rules/?id=${id}&status=publish,draft`,
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
}
