import { get } from 'svelte/store';
import { api } from '@piggy/lib';
import type { PluginOptionsAdminKeys } from '@piggy/types';
import type {
	GetEarnRuleByIdParams,
	GetEarnRuleByIdResponse,
	GetEarnRulesResponse,
	GetSettingByIdParams,
	GetSettingByIdResponse,
	GetSettingsResponse,
	GetSpendRuleByIdParams,
	GetSpendRuleByIdResponse,
	GetSpendRulesParams,
	GetSpendRulesResponse,
	SaveSettingsParams,
	SaveSettingsResponse,
	UpsertEarnRuleParams,
	UpsertEarnRuleResponse,
	UpsertSpendRuleParams,
	UpsertSpendRuleResponse
} from './types';

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
		const { data, error } = await api.post<SaveSettingsResponse>('/piggy/private/settings', {
			settings: Object.entries(get(settingsStore)).reduce(
				(acc, [key, setting]) => {
					acc[key] = {
						type: setting.type,
						value: setting.value
					};
					return acc;
				},
				{} as Record<
					string,
					{
						type: string;
						value: unknown;
					}
				>
			)
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}

	async getAllSettings(): Promise<GetSettingsResponse> {
		const { data, error } = await api.get<GetSettingsResponse>('/piggy/private/settings', {
			cache: 'no-store'
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}

	async getSetting<K extends PluginOptionsAdminKeys>({
		id
	}: GetSettingByIdParams<K>): Promise<GetSettingByIdResponse<K>> {
		const { data, error } = await api.get<GetSettingByIdResponse<K>>(
			`/piggy/private/settings/?id=${id}`,
			{
				cache: 'no-store'
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}

	async getEarnRuleById({ id }: GetEarnRuleByIdParams): Promise<GetEarnRuleByIdResponse> {
		const { data, error } = await api.get<GetEarnRuleByIdResponse>(
			`/piggy/private/earn-rules/?id=${id}`,
			{
				cache: 'no-store'
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}

	async getEarnRules(): Promise<GetEarnRulesResponse> {
		const { data, error } = await api.get<GetEarnRulesResponse>('/piggy/private/earn-rules', {
			cache: 'no-store'
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}

	async upsertEarnRule(earnRule: UpsertEarnRuleParams): Promise<UpsertEarnRuleResponse> {
		const { data, error } = await api.post<UpsertEarnRuleResponse>(
			'/piggy/private/earn-rules',
			earnRule
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}

	async getSpendRules(): Promise<GetSpendRulesResponse> {
		const { data, error } = await api.get<GetSpendRulesResponse>('/piggy/private/spend-rules', {
			cache: 'no-store'
		});

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}

	async upsertSpendRule(spendRule: UpsertSpendRuleParams): Promise<UpsertSpendRuleResponse> {
		const { data, error } = await api.post<UpsertSpendRuleResponse>(
			'/piggy/private/spend-rules',
			spendRule
		);

		if (error ?? !data) {
			if (error) {
				throw new SettingsAdminApiError(error.status, error.statusText, error.data);
			}

			throw new SettingsAdminApiError(500, 'No data returned', 'No data returned');
		}

		return data;
	}
}
