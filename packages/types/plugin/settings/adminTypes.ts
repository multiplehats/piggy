import { z } from 'zod';

export const zFieldTypes = z.enum([
	'checkbox',
	'color',
	'number',
	'select',
	'multiselect',
	'text',
	'textarea',
	'object',
	'api_key'
]);
export const zCheckboxValue = z.enum(['on', 'off']);
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

export const zToggle = zSettingsBaseField.extend({
	type: z.literal('checkbox'),
	default: zCheckboxValue,
	value: zCheckboxValue
});
export type Toggle = z.infer<typeof zToggle>;

export const zSelect = zSettingsBaseField.extend({
	type: z.literal('select'),
	options: zSelectOptions,
	value: z.string()
});
export type Select = z.infer<typeof zSelect>;

export const zMultiSelect = zSettingsBaseField.extend({
	type: z.literal('multiselect'),
	options: z.array(zSelectOptions),
	value: z.array(z.string())
});
export type MultiSelect = z.infer<typeof zMultiSelect>;

export const zColor = zSettingsBaseField.extend({
	type: z.literal('color'),
	value: z.string()
});
export type Color = z.infer<typeof zColor>;

export const zNumber = zSettingsBaseField.extend({
	type: z.literal('number'),
	value: z.number(),
	attributes: z.object({
		min: z.number().optional(),
		max: z.number().optional(),
		step: z.number().optional()
	})
});
export type Number = z.infer<typeof zNumber>;

export const zText = zSettingsBaseField.extend({
	type: z.literal('text'),
	value: z.string()
});
export type Text = z.infer<typeof zText>;

export const zApiKey = zSettingsBaseField.extend({
	type: z.literal('api_key'),
	value: z.string()
});
export type ApiKey = z.infer<typeof zApiKey>;

export const zTextarea = zSettingsBaseField.extend({
	type: z.literal('textarea'),
	value: z.string()
});
export type Textarea = z.infer<typeof zTextarea>;
