import type { settingsState } from '$lib/stores/settings';
import type {
	PluginEarnRuleItemValues,
	PluginOptionsAdmin,
	PluginOptionsAdminKeys,
	PluginOptionType,
	PluginSpendRuleItemValues
} from '@piggy/types';
import type {
	EarnRuleValueItem,
	SpendRuleValueItem
} from '@piggy/types/plugin/settings/adminTypes';

export type SaveSettingsParams = typeof settingsState;
export type SaveSettingsResponse = PluginOptionsAdmin | null;

export type GetSettingsResponse = PluginOptionsAdmin;
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

// Spend rules
export type GetSpendRulesResponse = SpendRuleValueItem[];
export type GetSpendRulesParams = void;

export interface GetSpendRuleByIdParams {
	id: number | string;
}
export type GetSpendRuleByIdResponse = [SpendRuleValueItem];

export type UpsertSpendRuleParams = Partial<PluginSpendRuleItemValues> & {
	id?: string | number;
	title: string;
};

export type UpsertSpendRuleResponse = SpendRuleValueItem;
