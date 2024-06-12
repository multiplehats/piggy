export const QueryKeys = {
	settings: 'settings',
	settingById: 'settingById',
	earnRules: 'earnRules',
	earnRuleById: 'earnRuleById',
	apiKey: 'apiKey',
	shops: 'shops'
} as const;

export const MutationKeys = {
	setApiKey: 'setApiKey',
	saveSettings: 'saveSettings',
	upsertEarnRule: 'upsertEarnRule'
} as const;
