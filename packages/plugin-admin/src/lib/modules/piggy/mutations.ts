import type { CreateMutationOptions, DefaultError, QueryClient } from '@tanstack/svelte-query';
import { __ } from '@wordpress/i18n';
import { MutationKeys, QueryKeys } from '$lib/utils/query-keys';
import toast from 'svelte-french-toast';
import { PiggyService } from '.';
import type { AdminGetApiKeyResponse, AdminSetApiKeyParams, AdminSetApiKeyResponse } from './types';

const service = new PiggyService();

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
		onMutate: async () => {
			await queryClient.cancelQueries({
				queryKey: [QueryKeys.apiKey]
			});

			const previousApiKey = queryClient.getQueryData<AdminGetApiKeyResponse>([QueryKeys.apiKey]);

			if (opts.onMutateCb) {
				opts.onMutateCb();
			}

			return {
				previousApiKey: previousApiKey ?? null
			};
		},
		onSuccess: async (newApiKey) => {
			queryClient.setQueryData<AdminGetApiKeyResponse>([QueryKeys.apiKey], newApiKey);
			await queryClient.invalidateQueries({ queryKey: [QueryKeys.shops] });

			toast.success(__('API key updated', 'piggy'));

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newApiKey);
			}
		},
		...mutationOpts
	};
}
