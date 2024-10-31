export const QueryKeys = {
	settings: "settings",
	settingById: "settingById",
	earnRules: "earnRules",
	wcProducts: "wcProducts",
	earnRuleById: "earnRuleById",
	spendRules: "spendRules",
	spendRuleById: "spendRuleById",
	promotionRules: "promotionRules",
	promotionRuleById: "promotionRuleById",
	apiKey: "apiKey",
	leatShops: "shops",
	leatRewards: "leatRewards",
} as const;

export const MutationKeys = {
	setApiKey: "setApiKey",
	searchProducts: "searchProducts",
	saveSettings: "saveSettings",
	upsertEarnRule: "upsertEarnRule",
	upsertSpendRule: "upsertSpendRule",
} as const;
