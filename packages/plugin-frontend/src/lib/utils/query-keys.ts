export const QueryKeys = {
	cart: 'cart'
} as const;

export const MutationKeys = {
	addCartItem: 'addCartItem',
	addCartItemsBatch: 'addCartItemsBatch',
	removeCartItem: 'removeCartItem',
	updateCartItemQuantity: 'updateCartItemQuantity',
	applyCoupon: 'applyCoupon',
	removeCoupon: 'removeCoupon'
} as const;
