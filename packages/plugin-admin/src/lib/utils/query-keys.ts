export const QueryKeys = {
	settings: "settings",
	settingById: "settingById",
	earnRules: "earnRules",
	wcProducts: "wcProducts",
	wcCategories: "wcCategories",
	earnRuleById: "earnRuleById",
	spendRules: "spendRules",
	spendRuleById: "spendRuleById",
	promotionRules: "promotionRules",
	promotionRuleById: "promotionRuleById",
	apiKey: "apiKey",
	leatShops: "shops",
	leatRewards: "leatRewards",
	webhooks: "webhooks",
} as const;

export const MutationKeys = {
	setApiKey: "setApiKey",
	searchProducts: "searchProducts",
	saveSettings: "saveSettings",
	searchCategories: "searchCategories",
	upsertEarnRule: "upsertEarnRule",
	upsertSpendRule: "upsertSpendRule",
	upsertPromotionRule: "upsertPromotionRule",
	syncWebhooks: "syncWebhooks",
} as const;
