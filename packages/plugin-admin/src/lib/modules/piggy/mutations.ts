import type { CreateMutationOptions, DefaultError, QueryClient } from '@tanstack/svelte-query';
import { __ } from '@wordpress/i18n';
import type { settingsState } from '$lib/stores/settings';
import { MutationKeys, QueryKeys } from '$lib/utils/query-keys';
import toast from 'svelte-french-toast';
import type { PluginOptionsAdminKeyValue } from '@piggy/types';
import { PiggyAdminService } from '.';
import type { AdminSetApiKeyParams, AdminSetApiKeyResponse } from './types';

const service = new PiggyAdminService();

interface SetApiKeyOptions {
	onSuccessCb?: (newApiKey: AdminSetApiKeyResponse) => void;
	onMutateCb?: () => void;
}

type SetApiKeyMutationConfig = CreateMutationOptions<
	AdminSetApiKeyResponse,
	DefaultError,
	AdminSetApiKeyParams
>;

export function setApiKeyMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<SetApiKeyMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess' | 'onMutate'>
	> = {},
	opts: SetApiKeyOptions = {}
): SetApiKeyMutationConfig {
	return {
		mutationKey: [MutationKeys.setApiKey],
		mutationFn: (params) => service.setApiKey(params),
		retry: true,
		onMutate: () => {
			if (opts.onMutateCb) {
				opts.onMutateCb();
			}
		},
		onSuccess: async (newApiKey) => {
			// queryClient.setQueryData<AdminGetApiKeyResponse>([QueryKeys.apiKey], newApiKey);
			await queryClient.invalidateQueries({ queryKey: [QueryKeys.shops] });

			toast.success(__('API key updated', 'piggy'));

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newApiKey);
			}
		},
		...mutationOpts
	};
}

type SaveSettingsMutationConfig = CreateMutationOptions<
	PluginOptionsAdminKeyValue,
	DefaultError,
	typeof settingsState
>;

export function saveSettingsMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<SaveSettingsMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess' | 'onMutate'>
	> = {}
): SaveSettingsMutationConfig {
	return {
		mutationKey: [MutationKeys.saveSettings],
		mutationFn: (params) => service.saveSettings(params),
		retry: false,
		...mutationOpts
	};
}
