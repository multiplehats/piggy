export const QueryKeys = {
	settings: 'settings',
	settingById: 'settingById',
	earnRules: 'earnRules',
	earnRuleById: 'earnRuleById',
	spendRules: 'spendRules',
	spendRuleById: 'spendRuleById',
	apiKey: 'apiKey',
	piggyShops: 'shops',
	piggyRewards: 'piggyRewards'
} as const;

export const MutationKeys = {
	setApiKey: 'setApiKey',
	saveSettings: 'saveSettings',
	upsertEarnRule: 'upsertEarnRule',
	upsertSpendRule: 'upsertSpendRule'
} as const;
