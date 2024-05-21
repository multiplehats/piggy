import type { DefaultError, QueryKey, UndefinedInitialDataOptions } from '@tanstack/svelte-query';
import { QueryKeys } from '$lib/utils/query-keys';
import type { Cart } from '@piggy/types';
import { CartApiService } from './cart.service';

const service = new CartApiService();

export function getCartQueryConfig(): UndefinedInitialDataOptions<
	Cart,
	DefaultError,
	Cart,
	QueryKey
> {
	return {
		queryKey: [QueryKeys.cart],
		retry: false,
		queryFn: async () => await service.getCartData(),
		refetchOnWindowFocus: true
	};
}
