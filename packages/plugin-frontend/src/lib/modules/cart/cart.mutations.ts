import type { CreateMutationOptions, DefaultError, QueryClient } from '@tanstack/svelte-query';
import { __, sprintf } from '@wordpress/i18n';
import { CartApiService } from '$lib/modules/cart/cart.service';
import type { AddCartItemBatchParams } from '$lib/modules/cart/cart.service';
import { triggerAddedToCartEvent, triggerFragmentRefresh } from '$lib/utils/legacyEvents';
import { MutationKeys, QueryKeys } from '$lib/utils/query-keys';
import type { Cart, CartCouponItem, CartItem } from '@piggy/types';

const service = new CartApiService();

interface AddCartItemOptions {
	onSuccessCb?: (cart: Cart) => void;
	onMutateCb?: () => void;
}

type AddCartItemMutationConfig = CreateMutationOptions<
	Cart,
	DefaultError,
	AddCartItemBatchParams,
	{ previousCart: Cart | null }
>;

export function addCartItemMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<AddCartItemMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess' | 'onMutate'>
	> = {},
	opts: AddCartItemOptions = {}
): AddCartItemMutationConfig {
	return {
		mutationKey: [MutationKeys.addCartItem],
		mutationFn: (params) => service.addCartItem(params),
		retry: true,
		onMutate: async () => {
			await queryClient.cancelQueries({
				queryKey: [QueryKeys.cart]
			});

			const previousCart = queryClient.getQueryData<Cart>([QueryKeys.cart]);

			if (opts.onMutateCb) {
				opts.onMutateCb();
			}

			return {
				previousCart: previousCart ?? null
			};
		},
		onSuccess: (newCart) => {
			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);
			triggerAddedToCartEvent({ preserveCartData: true });
			triggerFragmentRefresh();
			//toast.success(__('Product has been added to your cart.', 'piggy'));

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newCart);
			}
		},
		onError: async (error, variables, data) => {
			// This add to cart mutation normally hydrates the cart on return. `
			// But since it has thrown an error, we need to try to get full cart as it may be empty.
			if (!data?.previousCart) {
				await queryClient.refetchQueries({
					queryKey: [QueryKeys.cart]
				});
			}
		},
		...mutationOpts
	};
}

type AddCartItemBatchMutationConfig = CreateMutationOptions<
	Cart,
	DefaultError,
	AddCartItemBatchParams[],
	{ previousCart: Cart | null }
>;

export function addCartItemsBatchMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<AddCartItemBatchMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess'>
	> = {},
	opts: AddCartItemOptions = {}
): AddCartItemBatchMutationConfig {
	return {
		mutationKey: [MutationKeys.addCartItemsBatch],
		mutationFn: (params) => service.addCartItemsBatch(params),
		onMutate: async () => {
			await queryClient.cancelQueries({
				queryKey: [QueryKeys.cart]
			});

			const previousCart = queryClient.getQueryData<Cart>([QueryKeys.cart]);

			if (opts.onMutateCb) {
				opts.onMutateCb();
			}

			return {
				previousCart: previousCart ?? null
			};
		},
		onSuccess: (newCart) => {
			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);

			// Refresh fragments
			triggerAddedToCartEvent({ preserveCartData: true });
			triggerFragmentRefresh();

			//toast.success(__('The products have been added to your cart.', 'piggy'));

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newCart);
			}
		},
		onError: async (error, variables, data) => {
			// This add to cart mutation normally hydrates the cart on return. `
			// But since it has thrown an error, we need to try to get full cart as it may be empty.
			if (!data?.previousCart) {
				await queryClient.refetchQueries({
					queryKey: [QueryKeys.cart]
				});
			}
		},
		...mutationOpts
	};
}

type RemoveCartItemMutationConfig = CreateMutationOptions<Cart, DefaultError, CartItem, Cart>;

export function removeCartItemMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<
			RemoveCartItemMutationConfig,
			'mutationKey' | 'mutationFn' | 'onSuccess' | 'onMutate' | 'onError'
		>
	> = {},
	opts: {
		onSuccessCb?: (cart: Cart, variables: CartItem) => void;
	} = {}
): RemoveCartItemMutationConfig {
	return {
		mutationKey: [MutationKeys.removeCartItem],
		mutationFn: ({ key }) => service.removeCartItem(key),
		onSuccess: (newCart, variables) => {
			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);
			triggerFragmentRefresh();

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newCart, variables);
			}
		},
		onMutate: async ({ key }) => {
			await queryClient.cancelQueries({
				queryKey: [QueryKeys.cart]
			});

			const previousCart = queryClient.getQueryData<Cart>([QueryKeys.cart]);
			const previousCartItem = previousCart?.items.find((item) => item.key === key);

			if (!previousCart || !previousCartItem) {
				return undefined;
			}

			const newCart = {
				...previousCart,
				items: previousCart.items.filter((item) => item.key !== key)
			};

			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);

			return newCart;
		},
		onError(error, variables, previousCart) {
			queryClient.setQueryData<Cart>([QueryKeys.cart], previousCart);

			const previousCartItem = previousCart?.items.find((item) => item.key === variables.key);

			if (previousCartItem) {
				toast.error(
					sprintf(
						/** @preserve translators: %s is the product name */
						__('There was an error removing %s from your cart.', 'piggy'),
						previousCartItem.name
					)
				);
			} else {
				toast.error(__('There was an error removing the product from your cart.', 'piggy'));
			}

			return previousCartItem;
		},
		...mutationOpts
	};
}

type updateCartItemQuantityConfig = CreateMutationOptions<
	Cart,
	DefaultError,
	{ lineItem: CartItem; newQuantity: CartItem['quantity'] },
	Cart
>;

export function updateCartItemQuantityConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<
			updateCartItemQuantityConfig,
			'mutationKey' | 'mutationFn' | 'onSuccess' | 'onMutate' | 'onError'
		>
	> = {},
	opts: {
		onSuccessCb?: (cart: Cart) => void;
	} = {}
): updateCartItemQuantityConfig {
	return {
		mutationKey: [MutationKeys.updateCartItemQuantity],
		mutationFn: ({ lineItem: { key }, newQuantity }) => {
			return service.updateCartItemQuantity({
				key,
				quantity: newQuantity
			});
		},
		onSuccess: (newCart) => {
			triggerFragmentRefresh();

			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newCart);
			}
		},
		onMutate: async ({ lineItem, newQuantity }) => {
			await queryClient.cancelQueries({
				queryKey: [QueryKeys.cart]
			});

			const previousCart = queryClient.getQueryData<Cart>([QueryKeys.cart]);
			const previousCartItem = previousCart?.items.find((item) => item.key === lineItem.key);

			if (!previousCart || !previousCartItem) {
				return undefined;
			}

			const newCart = {
				...previousCart,
				items: previousCart.items.map((item) => {
					if (item.key === lineItem.key) {
						return {
							...item,
							quantity: newQuantity
						};
					}

					return item;
				})
			};

			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);

			return newCart;
		},
		onError(error, data, previousCart) {
			queryClient.setQueryData<Cart>([QueryKeys.cart], previousCart);
		},
		...mutationOpts
	};
}

type AddCouponMutationConfig = CreateMutationOptions<
	Cart,
	DefaultError,
	{ couponCode: string },
	unknown
>;

export function addCouponMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<AddCouponMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess'>
	> = {},
	opts: {
		onSuccessCb?: (cart: Cart) => void;
	} = {}
): AddCouponMutationConfig {
	return {
		mutationKey: [MutationKeys.applyCoupon],
		mutationFn: ({ couponCode }) => service.addCoupon(couponCode),
		onSuccess: (newCart, { couponCode }) => {
			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);
			triggerFragmentRefresh();
			//toast.success(sprintf(__('Coupon "%s" has been added to your cart.', 'piggy'), couponCode));

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newCart);
			}
		},
		...mutationOpts
	};
}

type RemoveCouponMutationConfig = CreateMutationOptions<
	Cart,
	DefaultError,
	{ couponItem: CartCouponItem },
	Cart
>;

export function removeCouponMutationConfig(
	queryClient: QueryClient,
	mutationOpts: Partial<
		Omit<RemoveCouponMutationConfig, 'mutationKey' | 'mutationFn' | 'onSuccess'>
	> = {},
	opts: { onSuccessCb?: (cart: Cart, variables: { couponItem: CartCouponItem }) => void } = {}
): RemoveCouponMutationConfig {
	return {
		mutationKey: [MutationKeys.removeCoupon],
		mutationFn: ({ couponItem: { code } }) => service.removeCoupon(code),
		onMutate: async ({ couponItem }) => {
			await queryClient.cancelQueries({
				queryKey: [QueryKeys.cart]
			});

			const previousCart = queryClient.getQueryData<Cart>([QueryKeys.cart]);

			if (!previousCart) {
				return undefined;
			}

			const newCart = {
				...previousCart,
				coupons: previousCart.coupons.filter((item) => item.code !== couponItem.code)
			};

			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);

			return newCart;
		},
		onSuccess: (newCart, { couponItem }) => {
			queryClient.setQueryData<Cart>([QueryKeys.cart], newCart);
			triggerFragmentRefresh();

			if (opts.onSuccessCb) {
				opts.onSuccessCb(newCart, { couponItem });
			}
		},
		onError: (errr, data, previousCart) => {
			queryClient.setQueryData<Cart>([QueryKeys.cart], previousCart);
		},
		...mutationOpts
	};
}
