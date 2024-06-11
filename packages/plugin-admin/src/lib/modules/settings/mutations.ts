import type { CreateMutationOptions, DefaultError, QueryClient } from '@tanstack/svelte-query';
import { __ } from '@wordpress/i18n';
import { MutationKeys, QueryKeys } from '$lib/utils/query-keys';
import toast from 'svelte-french-toast';
import { SettingsAdminService } from '.';
import type {
	SaveSettingsParams,
	SaveSettingsResponse,
	UpsertEarnRuleParams,
	UpsertEarnRuleResponse
} from './types';

const service = new SettingsAdminService();

type SaveSettingsMutationConfig = CreateMutationOptions<
	SaveSettingsResponse,
	DefaultError,
	SaveSettingsParams
>;

export function saveSettingsMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<SaveSettingsMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess' | 'onMutate'>
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
			await queryClient.invalidateQueries({ queryKey: [QueryKeys.shops] });

			toast.success(__('Settings saved updated', 'piggy'));

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newSettings);
			}
		},
		...mutationOpts
	};
}

type UpsertEarnRuleMutationConfig = CreateMutationOptions<
	UpsertEarnRuleResponse[0],
	DefaultError,
	UpsertEarnRuleParams
>;

export function upsertEarnRuleMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<UpsertEarnRuleMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess' | 'onMutate'>
	> = {},
	opts: {
		onSuccessCb?: (newRule: UpsertEarnRuleResponse) => void;
		onMutateCb?: () => void;
	} = {}
): UpsertEarnRuleMutationConfig {
	return {
		mutationKey: [MutationKeys.upsertEarnRule],
		mutationFn: async (params) => {
			const data = await service.upsertEarnRule(params);

			return data[0];
		},
		retry: true,
		onMutate: () => {
			if (opts.onMutateCb) {
				opts.onMutateCb();
			}
		},
		onSuccess: (newRule) => {
			toast.success(__('Earn rule saved', 'piggy'));

			if (opts.onSuccessCb) {
				opts.onSuccessCb([newRule]);
			}
		},
		...mutationOpts
	};
}
