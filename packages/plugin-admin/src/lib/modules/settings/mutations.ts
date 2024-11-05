import type { CreateMutationOptions, DefaultError, QueryClient } from "@tanstack/svelte-query";
import type {
	SaveSettingsParams,
	SaveSettingsResponse,
	UpsertEarnRuleParams,
	UpsertEarnRuleResponse,
	UpsertPromotionRuleParams,
	UpsertPromotionRuleResponse,
	UpsertSpendRuleParams,
	UpsertSpendRuleResponse,
} from "./types";
import { SettingsAdminService } from ".";
import { MutationKeys, QueryKeys } from "$lib/utils/query-keys";

const service = new SettingsAdminService();

type SaveSettingsMutationConfig = CreateMutationOptions<
	SaveSettingsResponse,
	DefaultError,
	SaveSettingsParams
>;

export function saveSettingsMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<SaveSettingsMutationConfig, "mutationKey" | "mutationFn" | "onSuccess" | "onMutate">
	> = {},
	opts: {
		onSuccessCb?: (newSettings: SaveSettingsResponse) => void;
		onMutateCb?: () => void;
	} = {}
): SaveSettingsMutationConfig {
	return {
		mutationKey: [MutationKeys.saveSettings],
		mutationFn: (params) => service.saveSettings(params),
		retry: true,
		onMutate: () => {
			if (opts.onMutateCb) {
				opts.onMutateCb();
			}
		},
		onSuccess: async (newSettings) => {
			await queryClient.invalidateQueries({ queryKey: [QueryKeys.leatShops] });

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newSettings);
			}
		},
		...mutationOpts,
	};
}

type UpsertEarnRuleMutationConfig = CreateMutationOptions<
	UpsertEarnRuleResponse,
	DefaultError,
	UpsertEarnRuleParams
>;

export function upsertEarnRuleMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<UpsertEarnRuleMutationConfig, "mutationKey" | "mutationFn" | "onSuccess" | "onMutate">
	> = {},
	opts: {
		onSuccessCb?: (newRule: UpsertEarnRuleResponse) => void;
		onMutateCb?: (earnRule: UpsertEarnRuleParams) => void;
	} = {}
): UpsertEarnRuleMutationConfig {
	return {
		mutationKey: [MutationKeys.upsertEarnRule],
		mutationFn: async (params) => {
			const data = await service.upsertEarnRule(params);

			return data;
		},
		retry: true,
		onMutate: async (earnRule) => {
			await queryClient.refetchQueries({ queryKey: [QueryKeys.earnRules] });

			if (opts.onMutateCb) {
				opts.onMutateCb(earnRule);
			}
		},
		onSuccess: async (newRule) => {
			if (opts.onSuccessCb) {
				opts.onSuccessCb(newRule);
			}
		},
		...mutationOpts,
	};
}

type UpsertSpendRuleMutationConfig = CreateMutationOptions<
	UpsertSpendRuleResponse,
	DefaultError,
	UpsertSpendRuleParams
>;

export function upsertSpendRuleMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<UpsertSpendRuleMutationConfig, "mutationKey" | "mutationFn" | "onSuccess" | "onMutate">
	> = {},
	opts: {
		onSuccessCb?: (newRule: UpsertSpendRuleResponse) => void;
		onMutateCb?: (spendRule: UpsertSpendRuleParams) => void;
	} = {}
): UpsertSpendRuleMutationConfig {
	return {
		mutationKey: [MutationKeys.upsertSpendRule],
		mutationFn: async (params) => {
			const data = await service.upsertSpendRule(params);

			return data;
		},
		retry: true,
		onMutate: async (earnRule) => {
			await queryClient.refetchQueries({ queryKey: [QueryKeys.earnRules] });

			if (opts.onMutateCb) {
				opts.onMutateCb(earnRule);
			}
		},
		onSuccess: (newRule) => {
			if (opts.onSuccessCb) {
				opts.onSuccessCb(newRule);
			}
		},
		...mutationOpts,
	};
}

type UpsertPromotionRuleMutationConfig = CreateMutationOptions<
	UpsertPromotionRuleResponse,
	DefaultError,
	UpsertPromotionRuleParams
>;

export function upsertPromotionRuleMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<
			UpsertPromotionRuleMutationConfig,
			"mutationKey" | "mutationFn" | "onSuccess" | "onMutate"
		>
	> = {},
	opts: {
		onSuccessCb?: (newRule: UpsertPromotionRuleResponse) => void;
		onMutateCb?: (promotionRule: UpsertPromotionRuleParams) => void;
	} = {}
): UpsertPromotionRuleMutationConfig {
	return {
		mutationKey: [MutationKeys.upsertPromotionRule],
		mutationFn: async (params) => {
			const data = await service.upsertPromotionRule(params);

			return data;
		},
		onMutate: async (promotionRule) => {
			await queryClient.refetchQueries({ queryKey: [QueryKeys.promotionRules] });

			if (opts.onMutateCb) {
				opts.onMutateCb(promotionRule);
			}
		},
		onSuccess: (newRule) => {
			if (opts.onSuccessCb) {
				opts.onSuccessCb(newRule);
			}
		},
		...mutationOpts,
	};
}
