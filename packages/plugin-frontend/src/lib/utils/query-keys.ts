export const QueryKeys = {
	earnRules: "earnRules",
	spendRules: "spendRules",
	coupons: "coupons",
	contact: "contact",
	tiers: "tiers",
	cart: "cart",
} as const;

export const MutationKeys = {
	claimReward: "claimReward",
	claimSpendRule: "claimSpendRule",
	joinProgram: "joinProgram",
	applyCoupon: "applyCoupon",
} as const;
