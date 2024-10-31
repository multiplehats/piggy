import type {
	PluginEarnRuleItemValues,
	PluginOptionType,
	PluginOptionsAdmin,
	PluginOptionsAdminKeys,
	PluginSpendRuleItemValues,
} from "@leat/types";
import type {
	EarnRuleValueItem,
	PromotionRuleValueItem,
	SpendRuleValueItem,
} from "@leat/types/plugin/settings/adminTypes";
import type { settingsState } from "$lib/stores/settings";

export type SaveSettingsParams = typeof settingsState;
export type SaveSettingsResponse = PluginOptionsAdmin | null;

export type GetSettingsResponse = PluginOptionsAdmin;
export type GetSettingsParams = void;

export type GetSettingByIdParams<K extends PluginOptionsAdminKeys> = {
	id: K;
};

export type GetSettingByIdResponse<K extends PluginOptionsAdminKeys> = PluginOptionType<K>;

export type GetEarnRulesResponse = EarnRuleValueItem[];
export type GetEarnRulesParams = void;

export type GetEarnRuleByIdParams = {
	id: number | string;
};
export type GetEarnRuleByIdResponse = [EarnRuleValueItem];

export type UpsertEarnRuleParams = Partial<PluginEarnRuleItemValues> & {
	id?: string | number;
	title: string;
};

export type UpsertEarnRuleResponse = EarnRuleValueItem;

// Spend rules
export type GetSpendRulesResponse = SpendRuleValueItem[];
export type GetSpendRulesParams = void;

export type GetSpendRuleByIdParams = {
	id: number | string;
};
export type GetSpendRuleByIdResponse = [SpendRuleValueItem];

export type UpsertSpendRuleParams = Partial<PluginSpendRuleItemValues> & {
	id?: string | number;
	title: string;
};

export type UpsertSpendRuleResponse = SpendRuleValueItem;

// Promotion rules
export type GetPromotionRulesResponse = PromotionRuleValueItem[];
export type GetPromotionRulesParams = void;

export type GetPromotionRuleByIdParams = {
	id: number | string;
};
export type GetPromotionRuleByIdResponse = [PromotionRuleValueItem];
