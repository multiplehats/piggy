import type { ProductResponseItem } from './wc-types';

/**
 * API Route extensions.
 *
 * @see src/StoreApiExtension/Api.php
 */
export interface PiggyCartExtensionData {
	piggy: {
		free_shipping_meter?: {
			type: string;
			text: string;
			text_achieved: string;
			country_code: string;
			flag: string;
			requires: string;
			ignore_discounts: boolean;
			required_amount: string;
		};
		product_recommendation_engine?: {
			products: {
				upsells: ProductResponseItem[];
				cross_sells: ProductResponseItem[];
				custom?: ProductResponseItem[];
			};
		};
		product_suggestions?: {
			products: {
				global: ProductResponseItem[];
			};
		};
		common: {
			customerOutsideBase: boolean;
			estimatedForPrefix: string;
			customerCountry: string;
			/**
			 * We use this over the default "hasCalculatedShipping" because it's more accurate.
			 *
			 * @readonly
			 */
			hasCalculatedShipping: boolean;
		};
	};
}
