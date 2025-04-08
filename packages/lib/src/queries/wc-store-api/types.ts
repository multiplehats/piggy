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

export type BatchResponse = {
	responses: {
		body: StoreAPIResponse;
	}[];
};

export type StoreAPIResponse = {
	items_count: number;
	items: CartItem[];
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

export type StoreAPICartResponse = {
	items: CartItemResponse[];
	items_count: number;
	totals: {
		total_items: string;
		total_price: string;
		currency_symbol: string;
	};
};
