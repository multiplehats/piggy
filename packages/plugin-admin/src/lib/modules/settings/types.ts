import type {
	PluginEarnRuleItemValues,
	PluginOptionType,
	PluginOptionsAdmin,
	PluginOptionsAdminKeys,
	PluginPromotionRuleItemValues,
	PluginSpendRuleItemValues,
} from "@leat/types";
import type {
	EarnRuleValueItem,
	PromotionRuleValueItem,
	SpendRuleValueItem,
} from "@leat/types/plugin/settings/adminTypes";
import type { settingsState } from "$lib/stores/settings";

export type TaskInformation = {
	type: "background_task";
	is_processing: boolean;
	is_queued: boolean;
	is_paused: boolean;
	is_cancelled: boolean;
	total_items: number;
	items_processed: number;
	remaining_items: number;
	status: string;
	last_process: {
		timestamp: number;
		memory_used: number;
		execution_time: number;
		items_processed: number;
		status: string;
	};
};

export type SyncStatus = {
	type: "sync";
	total_items: number;
	items_processed: number;
	items_updated: number;
	items_created: number;
	items_deleted: number;
	is_processing: boolean;
	last_sync: {
		timestamp: number;
		success: boolean;
		error?: string;
	};
};

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

export type UpsertPromotionRuleParams = Partial<PluginPromotionRuleItemValues> & {
	id?: string | number;
	title: string;
};

export type UpsertPromotionRuleResponse = PromotionRuleValueItem;

export type GetSyncVouchersInformationResponse = TaskInformation;
export type GetSyncVouchersInformationParams = {
	id: string;
};

type Webhook = {
	id: string;
	name: string;
	event_type: string;
	url: string;
	status: string;
};

type RequiredWebhook = {
	name: string;
	event_type: string;
	attributes?: string[];
};

export type GetWebhooksResponse = {
	webhooks: Webhook[];
	required_webhooks: Record<string, RequiredWebhook>;
};

export type SyncWebhooksResponse = {
	webhooks: Webhook[];
	success: true;
};
