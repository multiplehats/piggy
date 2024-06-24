import type { DefaultError, QueryKey, UndefinedInitialDataOptions } from '@tanstack/svelte-query';
import { QueryKeys } from '$lib/utils/query-keys';
import { PiggyAdminService } from '.';
import type { AdminGetApiKeyResponse, GetShopsResponse } from './types';

const service = new PiggyAdminService();

export function getApiKeyQueryConfig(): UndefinedInitialDataOptions<
	AdminGetApiKeyResponse,
	DefaultError,
	AdminGetApiKeyResponse,
	QueryKey
> {
	return {
		queryKey: [QueryKeys.apiKey],
		retry: false,
		queryFn: async () => await service.getApiKey(),
		refetchOnWindowFocus: true
	};
}

export function getShopsQueryConfig(): UndefinedInitialDataOptions<
	GetShopsResponse,
	DefaultError,
	GetShopsResponse,
	QueryKey
> {
	return {
		queryKey: [QueryKeys.piggyShops],
		retry: false,
		queryFn: async () => await service.getShops(),
		refetchOnWindowFocus: true
	};
}
