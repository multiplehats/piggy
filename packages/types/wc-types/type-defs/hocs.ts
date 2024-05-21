/**
 * External dependencies
 */

import type { ProductCategoryResponseItem, ProductResponseItem } from '../type-defs';

export interface WithInjectedProductVariations {
	error: unknown | null;
	/**
	 * The id of the currently expanded product
	 */
	expandedProduct: number | null;
	variations: Record<number, ProductResponseItem[]>;
	variationsLoading: boolean;
}

export interface WithInjectedSearchedProducts {
	error: unknown | null;
	isLoading: boolean;
	onSearch: ((search: string) => void) | null;
	products: ProductResponseItem[];
	selected: number[];
}

export interface WithInjectedSearchedCategories {
	error: unknown | null;
	isLoading: boolean;
	categories: ProductCategoryResponseItem[];
	selected: number[];
}

export interface WithInjectedInstanceId {
	instanceId: string | number;
}
