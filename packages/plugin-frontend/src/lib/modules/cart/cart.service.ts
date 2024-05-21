import { api } from '$lib/api';
import { camelCase, mapKeys } from 'lodash-es';
import type { Cart, CartItem, CartResponse, PiggyAddToCartContext } from '@piggy/types';

export class WooCommerceStoreApiError extends Error {
	status: number;
	statusText: string;
	data: string;
	message: string;

	constructor(status: number, statusText: string, data: string) {
		super(`WooCommerceStoreApiError: ${statusText}`);
		this.status = status;
		this.statusText = statusText;
		this.data = data;
		this.message = data;
	}
}

export interface AddCartItemBatchParams {
	id: number;
	quantity: CartItem['quantity'];
	variation?: CartItem['variation'];
	context?: PiggyAddToCartContext;
}

export class CartApiService {
	formatCartResponse(response: CartResponse): Cart {
		return mapKeys(response, (_, key) => camelCase(key)) as unknown as Cart;
	}

	async getCartData(): Promise<Cart> {
		const { data, error } = await api.get<CartResponse>('/wc/store/v1/cart', {
			cache: 'no-store'
		});

		if (error ?? !data) {
			if (error) {
				throw new WooCommerceStoreApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return this.formatCartResponse(data);
	}

	async removeCartItem(key: string): Promise<Cart> {
		const { data, error } = await api.post<CartResponse>(
			`/wc/store/v1/cart/remove-item`,
			{
				key
			},
			{
				cache: 'no-store'
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new WooCommerceStoreApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return this.formatCartResponse(data);
	}

	async addCartItem({ id, quantity, variation, context }: AddCartItemBatchParams): Promise<Cart> {
		const { data, error } = await api.post<CartResponse>(
			'/wc/store/v1/cart/add-item',
			{
				id,
				quantity,
				variation,
				piggy: {
					context: context
				}
			},
			{
				cache: 'no-store'
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new WooCommerceStoreApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return this.formatCartResponse(data);
	}

	async addCartItemsBatch(items: AddCartItemBatchParams[]): Promise<Cart> {
		const requests = items.map((item) => ({
			path: '/wc/store/v1/cart/add-item',
			method: 'POST',
			cache: 'no-store',
			body: {
				id: item.id,
				quantity: item.quantity,
				variation: item.variation,
				piggy: {
					context: item.context
				}
			}
		}));

		const { data, error } = await api.post<{
			responses: { body: CartResponse }[];
			errors: Error[];
		}>('/wc/store/v1/batch', { requests });

		if (error ?? !data) {
			if (error) {
				throw new WooCommerceStoreApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		// Use the last response as the cart data.
		return this.formatCartResponse(data.responses[data.responses.length - 1].body);
	}

	async updateCartItemQuantity({
		key,
		quantity
	}: Pick<CartItem, 'key' | 'quantity'>): Promise<Cart> {
		const { data, error } = await api.post<CartResponse>(
			'/wc/store/v1/cart/update-item',
			{ key, quantity },
			{
				cache: 'no-store'
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new WooCommerceStoreApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return this.formatCartResponse(data);
	}

	async addCoupon(couponCode: string): Promise<Cart> {
		const { data, error } = await api.post<CartResponse>(
			`/wc/store/v1/cart/apply-coupon`,
			{
				code: couponCode
			},
			{
				cache: 'no-store'
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new WooCommerceStoreApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return this.formatCartResponse(data);
	}

	async removeCoupon(couponCode: string): Promise<Cart> {
		const { data, error } = await api.post<CartResponse>(
			`/wc/store/v1/cart/remove-coupon`,
			{
				code: couponCode
			},
			{
				cache: 'no-store'
			}
		);

		if (error ?? !data) {
			if (error) {
				throw new WooCommerceStoreApiError(error.status, error.statusText, error.data);
			}

			throw new Error('No data returned');
		}

		return this.formatCartResponse(data);
	}
}

export const cartApiService = new CartApiService();
