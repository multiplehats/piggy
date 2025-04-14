export type CartResponse = {
	success: boolean;
	data: {
		message: string;
		count?: number;
	};
};

export type AddCartItemBatchParams = {
	id: number;
	quantity: number;
	variation?: {
		attribute: string;
		value: string;
	}[];
};

export type CartItem = {
	id: string;
	name: string;
	quantity: number;
	key?: string;
	totals: {
		line_total: string;
		currency_symbol: string;
	};
};

export type CartItemResponse = {
	key: string;
	id: number;
	type: string;
	quantity: number;
	name: string;
	variation?: Array<{
		attribute: string;
		value: string;
	}>;
};

export type CartCoupon = {
	code: string;
	discount_type: string;
	totals: {
		total_discount: string;
		total_discount_tax: string;
		currency_code: string;
		currency_symbol: string;
		currency_minor_unit: number;
		currency_decimal_separator: string;
		currency_thousand_separator: string;
		currency_prefix: string;
		currency_suffix: string;
	};
};

export type StoreAPICartResponse = {
	items: CartItemResponse[];
	items_count: number;
	totals: {
		total_items: string;
		total_price: string;
		currency_symbol: string;
	};
	coupons: CartCoupon[];
};

export type BatchResponse = {
	responses: {
		body: StoreAPICartResponse;
	}[];
};
