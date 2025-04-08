/**
 * WooCommerce Store API Service
 */
import { api } from "../client.js";
import { LeatApiError } from "../errors.js";
import type {
	AddCartItemBatchParams,
	BatchResponse,
	StoreAPICartResponse,
	StoreAPIResponse,
} from "./types";

const baseUrl = "/wc/store/v1";

/**
 * Add a single item to cart directly
 *
 * @param id Product ID
 * @param quantity Quantity
 * @returns Promise with cart data
 */
export async function addSingleItemToCart(id: number, quantity = 1): Promise<StoreAPIResponse> {
	try {
		const { data, error } = await api.post<StoreAPIResponse>(`${baseUrl}/cart/add-item`, {
			id,
			quantity,
		});

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		if (!data) {
			throw new LeatApiError(400, "Bad Request", "No data returned from API");
		}

		return data;
	} catch (error) {
		console.error("Store API error adding single item:", error);
		throw error;
	}
}

/**
 * Add multiple items to cart in a batch
 *
 * @param items Array of items with id, quantity and optional variation
 * @returns Promise with cart data
 */
export async function addCartItems(items: AddCartItemBatchParams[]): Promise<StoreAPICartResponse> {
	try {
		// If only one item, use the direct method
		if (items.length === 1) {
			const response = await addSingleItemToCart(items[0].id, items[0].quantity);
			// Convert to the new response format
			return {
				items: response.items.map((item) => ({
					key: "",
					id: Number(item.id),
					type: "variation",
					quantity: item.quantity,
					name: item.name,
				})),
				items_count: response.items_count,
				totals: {
					total_items: "",
					total_price: "",
					currency_symbol: "",
				},
			};
		}

		// Create batch requests with full paths as required by the API
		const requests = items.map((item) => ({
			path: "/wc/store/v1/cart/add-item", // Full path as required by the API
			method: "POST",
			cache: "no-store",
			body: {
				id: item.id,
				quantity: item.quantity,
				variation: item.variation,
			},
		}));

		// Use the full cart endpoint to get the complete cart data
		const batchResponse = await api.post<BatchResponse>(`${baseUrl}/batch`, {
			requests,
		});

		if (batchResponse.error) {
			throw new LeatApiError(
				batchResponse.error.status,
				batchResponse.error.statusText,
				batchResponse.error.data
			);
		}

		// After batch request, get the full cart data
		await getCart();

		// Get the complete cart from the store API
		const fullCartResponse = await api.get<StoreAPICartResponse>(`${baseUrl}/cart`);

		if (fullCartResponse.error) {
			throw new LeatApiError(
				fullCartResponse.error.status,
				fullCartResponse.error.statusText,
				fullCartResponse.error.data
			);
		}

		if (!fullCartResponse.data) {
			throw new LeatApiError(400, "Bad Request", "No data returned from API");
		}

		return fullCartResponse.data;
	} catch (error) {
		console.error("Store API error:", error);
		throw error;
	}
}

/**
 * Get cart contents
 *
 * @returns Promise with cart data
 */
export async function getCart(): Promise<StoreAPIResponse> {
	try {
		const { data, error } = await api.get<StoreAPIResponse>(`${baseUrl}/cart`);

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		if (!data) {
			throw new LeatApiError(400, "Bad Request", "No data returned from API");
		}

		return data;
	} catch (error) {
		console.error("Store API error getting cart:", error);
		throw error;
	}
}

/**
 * Get variation IDs that are currently in the cart
 *
 * @returns Promise with array of variation IDs
 */
export async function getCartVariationIds(): Promise<number[]> {
	try {
		const cart = await getCart();

		// Extract variation IDs from cart items
		// The Store API response doesn't directly include variation_id in the type
		// but the actual response from WooCommerce includes it
		const variationIds = cart.items
			.filter((item) => item.id)
			.map((item) => {
				// Convert string ID to number and return it
				// This assumes the ID is the variation ID when a variation is in the cart
				return Number(item.id);
			})
			.filter((id) => !Number.isNaN(id) && id > 0);

		return variationIds;
	} catch (error) {
		console.error("Failed to get variation IDs from cart:", error);
		return [];
	}
}

/**
 * Remove an item from the cart by its key
 *
 * @param key The cart item key to remove
 * @returns Promise with cart data
 */
export async function removeCartItem(key: string): Promise<StoreAPIResponse> {
	try {
		const { data, error } = await api.post<StoreAPIResponse>(`${baseUrl}/cart/remove-item`, {
			key,
		});

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		if (!data) {
			throw new LeatApiError(400, "Bad Request", "No data returned from API");
		}

		return data;
	} catch (error) {
		console.error("Store API error removing item:", error);
		throw error;
	}
}

/**
 * Find cart item key by variation ID
 *
 * @param variationId The variation ID to find in the cart
 * @returns Promise with the cart item key or null if not found
 */
export async function findCartItemKeyByVariationId(variationId: number): Promise<string | null> {
	try {
		const cart = await getCart();

		// Find the cart item with the matching variation ID
		const cartItem = cart.items.find((item) => Number(item.id) === variationId);

		return cartItem?.key || null;
	} catch (error) {
		console.error("Failed to find cart item key:", error);
		return null;
	}
}

/**
 * Apply a coupon code to the cart
 *
 * @param code The coupon code to apply
 * @returns Promise with cart data
 */
export async function applyCoupon(code: string): Promise<StoreAPIResponse> {
	try {
		const { data, error } = await api.post<StoreAPIResponse>(`${baseUrl}/cart/apply-coupon`, {
			code,
		});

		if (error) {
			throw new LeatApiError(error.status, error.statusText, error.data);
		}

		if (!data) {
			throw new LeatApiError(400, "Bad Request", "No data returned from API");
		}

		return data;
	} catch (error) {
		console.error("Store API error applying coupon:", error);
		throw error;
	}
}
