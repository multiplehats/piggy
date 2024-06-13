import type { settingsState } from '$lib/stores/settings';
import type {
	PluginEarnRuleItemValues,
	PluginOptionsAdmin,
	PluginOptionsAdminKeys,
	PluginOptionsAdminKeyValue,
	PluginOptionType
} from '@piggy/types';
import type { EarnRuleValueItem } from '@piggy/types/plugin/settings/adminTypes';

export type SaveSettingsParams = typeof settingsState;
export type SaveSettingsResponse = PluginOptionsAdmin | null;

export type GetSettingsResponse = PluginOptionsAdminKeyValue;
export type GetSettingsParams = void;

export interface GetSettingByIdParams<K extends PluginOptionsAdminKeys> {
	id: K;
}

export type GetSettingByIdResponse<K extends PluginOptionsAdminKeys> = PluginOptionType<K>;

export type GetEarnRulesResponse = EarnRuleValueItem[];
export type GetEarnRulesParams = void;

export interface GetEarnRuleByIdParams {
	id: number | string;
}
export type GetEarnRuleByIdResponse = [EarnRuleValueItem];

export type UpsertEarnRuleParams = Partial<PluginEarnRuleItemValues> & {
	id?: string | number;
	title: string;
};

export type UpsertEarnRuleResponse = EarnRuleValueItem;
