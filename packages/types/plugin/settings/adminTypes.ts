import { z } from 'zod';

export const zFieldTypes = z.enum([
	'checkbox',
	'checkboxes',
	'color',
	'number',
	'select',
	'multiselect',
	'text',
	'textarea',
	'api_key',
	'translatable_text',
	'switch',
	'earn_rules',
	'date'
]);

export const zSelectOptionsItem = z.object({ label: z.string() });
export const zSelectOptions = z.record(zSelectOptionsItem);

export const zSettingsBaseField = z.object({
	id: z.string(),
	label: z.string(),
	default: z.string(),
	tooltip: z.string().optional(),
	placeholder: z.string().optional(),
	description: z.string().optional(),
	value: z.union([z.string(), z.number(), z.array(z.union([z.string(), z.number()]))]),
	type: zFieldTypes
});
export type SettingsBaseField = z.infer<typeof zSettingsBaseField>;

// Extended fields with specific constraints based on the field type
export const zCheckboxValue = z.enum(['on', 'off']);
export type CheckboxValue = z.infer<typeof zCheckboxValue>;
export const zCheckbox = zSettingsBaseField.extend({
	type: z.literal('checkbox'),
	default: zCheckboxValue,
	value: zCheckboxValue
});
export type Chdckbox = z.infer<typeof zCheckbox>;

export const zSwitchValue = z.enum(['on', 'off']);
export type SwitchValue = z.infer<typeof zSwitchValue>;
export const zSwitch = zSettingsBaseField.extend({
	type: z.literal('switch'),
	default: zCheckboxValue,
	value: zCheckboxValue
});
export type Toggle = z.infer<typeof zSwitch>;

export const zSelect = zSettingsBaseField.extend({
	type: z.literal('select'),
	options: zSelectOptions,
	value: z.string()
});
export type Select = z.infer<typeof zSelect>;

export const zMultiSelect = zSettingsBaseField.extend({
	type: z.literal('multiselect'),
	options: z.array(zSelectOptions),
	default: z.array(z.string()).or(z.tuple([])),
	value: z.array(z.string()).or(z.tuple([]))
});
export type MultiSelect = z.infer<typeof zMultiSelect>;

export const zCheckboxesOptionsItem = z.object({
	label: z.string(),
	tooltip: z.string().optional()
});
export const zCheckboxesOptions = z.record(zCheckboxesOptionsItem);
export type CheckboxesOptions = z.infer<typeof zCheckboxesOptions>;
export type CheckboxesOptionsItem = z.infer<typeof zCheckboxesOptionsItem>;
export const zCheckboxes = zSettingsBaseField.extend({
	type: z.literal('checkboxes'),
	options: zCheckboxesOptions,
	default: z.record(zCheckboxValue),
	value: z.record(zCheckboxValue)
});
export type Checkboxes = z.infer<typeof zCheckboxes>;

export const zColor = zSettingsBaseField.extend({
	type: z.literal('color'),
	value: z.string(),
	default: z.string()
});
export type Color = z.infer<typeof zColor>;

export const zNumber = zSettingsBaseField.extend({
	type: z.literal('number'),
	value: z.number().nullable(),
	default: z.number(),
	attributes: z.object({
		min: z.number().optional(),
		max: z.number().optional(),
		step: z.number().optional()
	})
});
export type Number = z.infer<typeof zNumber>;

export const zText = zSettingsBaseField.extend({
	type: z.literal('text'),
	value: z.string().nullable(),
	default: z.string()
});
export type Text = z.infer<typeof zText>;

export const zDate = zSettingsBaseField.extend({
	type: z.literal('date'),
	value: z.string(),
	default: z.string()
});
export type Date = z.infer<typeof zDate>;

export const zApiKey = zSettingsBaseField.extend({
	type: z.literal('api_key'),
	value: z.string().min(40).max(40)
});
export type ApiKey = z.infer<typeof zApiKey>;

export const zShopUuid = zSettingsBaseField.extend({
	type: z.literal('text'),
	value: z.string().min(36).max(36)
});
export type ShopUuid = z.infer<typeof zShopUuid>;

export const zTextarea = zSettingsBaseField.extend({
	type: z.literal('textarea'),
	value: z.string()
});
export type Textarea = z.infer<typeof zTextarea>;

export const zTranslatableText = zSettingsBaseField.extend({
	type: z.literal('translatable_text'),
	value: z.record(z.string()).nullable(),
	default: z.record(z.string()).nullable()
});
export type TranslatableText = z.infer<typeof zTranslatableText>;

// Earn rules
export const zEarnRuleType = z
	.literal('LIKE_ON_FACEBOOK')
	.or(z.literal('FOLLOW_ON_TIKTOK'))
	.or(z.literal('PLACE_ORDER'))
	.or(z.literal('CELEBRATE_BIRTHDAY'))
	.or(z.literal('FOLLOW_ON_INSTAGRAM'))
	.or(z.literal('CREATE_ACCOUNT'));

export type EarnRuleType = z.infer<typeof zEarnRuleType>;

export const zEarnRuleValueItem = z.object({
	id: z.number(),
	title: zText,
	label: zTranslatableText,
	status: zSelect.extend({
		default: z.literal('publish').or(z.literal('draft')),
		value: z.literal('publish').or(z.literal('draft'))
	}),
	type: zSelect.extend({
		value: zEarnRuleType
	}),
	piggyTierUuids: z.array(z.string()).or(z.tuple([])),
	createdAt: z.string(),
	updatedAt: z.string(),
	startsAt: zDate,
	expiresAt: zDate,
	completed: z.boolean().nullable().optional(),
	credits: zNumber,
	socialHandle: zText,
	excludedCollectionIds: z.array(z.string()).nullable().optional(),
	excludedProductIds: z.array(z.string()).nullable().optional(),
	minimumOrderAmount: zNumber
});
export type EarnRuleValueItem = z.infer<typeof zEarnRuleValueItem>;

// Spent rules
export const zSpendRuleType = z.literal('PRODUCT_DISCOUNT');

export type SpendRuleType = z.infer<typeof zSpendRuleType>;

export const zSpendRuleValueItem = z.object({
	id: z.number(),
	title: zText,
	label: zTranslatableText,
	status: zSelect.extend({
		default: z.literal('publish').or(z.literal('draft')),
		value: z.literal('publish').or(z.literal('draft'))
	}),
	type: zSelect.extend({
		value: zSpendRuleType
	}),
	createdAt: z.string(),
	updatedAt: z.string(),
	startsAt: zDate,
	expiresAt: zDate,
	completed: z.boolean().nullable().optional()
});
export type SpendRuleValueItem = z.infer<typeof zSpendRuleValueItem>;

export const zEarnRules = zSettingsBaseField.extend({
	type: z.literal('earn_rules'),
	default: z.array(zEarnRuleValueItem).or(z.tuple([])),
	value: z.array(zEarnRuleValueItem),
	options: z.array(
		z.object({
			type: z.string(),
			label: z.string(),
			fields: z
				.object({
					id: z.string(),
					label: z.string(),
					default: z.union([
						z.string(),
						z.number(),
						z.null(),
						z.array(z.union([z.string(), z.number()]))
					]),
					type: zFieldTypes
				})
				.array()
		})
	)
});

export type EarnRule = z.infer<typeof zEarnRules>;
