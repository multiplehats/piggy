import { z } from "zod";
import * as adminFields from "./adminTypes";

/**
 * Transforms a Zod schema by extracting the `value` field from each property.
 * Assumes that each property of the schema is an object that includes a `value` key.
 */
function transformSchema<T extends z.ZodRawShape>(
	schema: z.ZodObject<T>
): z.ZodObject<{ [K in keyof T]: ExtractValue<T[K]> }> {
	const newShape: Partial<{ [K in keyof T]: ExtractValue<T[K]> }> = {};

	// Iterate over each key in the original schema shape
	for (const key in schema.shape) {
		// eslint-disable-next-line no-prototype-builtins
		if (schema.shape.hasOwnProperty(key)) {
			const originalField = schema.shape[key as keyof T];

			// Check if the field is a refinement and extract the inner type
			// eslint-disable-next-line ts/no-explicit-any
			const getInnerType = (field: any): any => {
				if (field._def.typeName === "ZodEffects") {
					return getInnerType(field._def.schema);
				}
				return field;
			};

			const refinedField = getInnerType(originalField);

			// Safely checking if 'value' exists in 'refinedField.shape'
			if (
				refinedField &&
				typeof refinedField === "object" &&
				"shape" in refinedField &&
				typeof refinedField.shape === "object" &&
				refinedField?.shape &&
				"value" in refinedField.shape
			) {
				newShape[key as keyof T] = refinedField.shape.value as ExtractValue<T[keyof T]>;
			} else {
				// Optionally handle cases where 'value' does not exist or the shape is different
				throw new Error(`The field at key '${key}' does not match the expected structure.`);
			}
		}
	}

	// The new shape is explicitly cast to the full type as all fields are now assigned
	return z.object(newShape as { [K in keyof T]: ExtractValue<T[K]> });
}

// Utility type to extract the 'value' type from a Zod schema if it follows a specific pattern
type ExtractValue<S> =
	S extends z.ZodObject<infer U> ? (U extends { value: infer V } ? V : never) : never;

/**
 * Options interface for both the frontend and admin.
 * This is the base schema that is extended for each context.
 *
 * @note Do not specify fields here that are only used in the admin!
 */
export const zBasePluginOptions = z.object({
	plugin_enable: adminFields.zSwitch,
	plugin_reset: adminFields.zSwitch,
	credits_name: adminFields.zTranslatableText,
	credits_spend_rule_progress: adminFields.zTranslatableText,
	include_guests: adminFields.zSwitch,
	dashboard_show_join_program_cta: adminFields.zSwitch,
	reward_order_statuses: adminFields.zSelect,
	withdraw_order_statuses: adminFields.zCheckboxes,
	reward_order_parts: adminFields.zCheckboxes,
	marketing_consent_subscription: adminFields.zSwitch,
	earn_rules: adminFields.zEarnRules,
	dashboard_myaccount_title: adminFields.zTranslatableText,
	dashboard_title_logged_in: adminFields.zTranslatableText,
	dashboard_title_logged_out: adminFields.zTranslatableText,
	dashboard_join_cta: adminFields.zTranslatableText,
	dashboard_join_program_cta: adminFields.zTranslatableText,
	dashboard_title_join_program: adminFields.zTranslatableText,
	dashboard_login_cta: adminFields.zTranslatableText,
	dashboard_nav_coupons: adminFields.zTranslatableText,
	dashboard_nav_coupons_empty_state: adminFields.zTranslatableText,
	dashboard_nav_tiers: adminFields.zTranslatableText,
	dashboard_show_tiers: adminFields.zSwitch,
	dashboard_coupons_loading_state: adminFields.zTranslatableText,
	dashboard_nav_earn: adminFields.zTranslatableText,
	dashboard_nav_rewards: adminFields.zTranslatableText,
	dashboard_nav_activity: adminFields.zTranslatableText,
	dashboard_earn_cta: adminFields.zTranslatableText,
	dashboard_spend_cta: adminFields.zTranslatableText,
	giftcard_order_status: adminFields.zSelect,
	giftcard_withdraw_order_statuses: adminFields.zCheckboxes,
	giftcard_disable_recipient_email: adminFields.zSwitch,
});

/**
 * Admin options interface.
 * This schema extends the base schema and adds admin-specific fields.
 */
export const zPluginOptionsAdmin = zBasePluginOptions.extend({
	api_key: adminFields.zApiKey,
	shop_uuid: adminFields.zShopUuid,
});

export type PluginOptionsAdmin = z.infer<typeof zPluginOptionsAdmin>;
export type PluginOptionsAdminKeys = keyof PluginOptionsAdmin;

export const ZPluginOptionsAdminKeyValue = transformSchema(zPluginOptionsAdmin);
export type PluginOptionsAdminKeyValue = z.infer<typeof ZPluginOptionsAdminKeyValue>;

export type PluginOptionType<K extends PluginOptionsAdminKeys> = PluginOptionsAdmin[K];

export const zPluginEarnRuleItemValues = transformSchema(
	adminFields.zEarnRuleValueItem.pick({
		label: true,
		status: true,
		type: true,
		title: true,
		startsAt: true,
		expiresAt: true,
		minimumOrderAmount: true,
		socialHandle: true,
		credits: true,
	})
);
export type PluginEarnRuleItemValues = z.infer<typeof zPluginEarnRuleItemValues>;

export const zPluginSpendRuleItemValues = transformSchema(
	adminFields.zSpendRuleValueItem.pick({
		label: true,
		status: true,
		type: true,
		title: true,
		selectedProducts: true,
		selectedCategories: true,
		selectedTags: true,
		startsAt: true,
		expiresAt: true,
		instructions: true,
		creditCost: true,
		description: true,
		fulfillment: true,
		discountType: true,
		discountValue: true,
		minimumPurchaseAmount: true,
		limitUsageToXItems: true,
	})
);
export type PluginSpendRuleItemValues = z.infer<typeof zPluginSpendRuleItemValues>;

export const zPluginPromotionRuleItemValues = transformSchema(
	adminFields.zPromotionRuleValueItem.pick({
		label: true,
		selectedProducts: true,
		discountValue: true,
		discountType: true,
		minimumPurchaseAmount: true,
		individualUse: true,
	})
);
export type PluginPromotionRuleItemValues = z.infer<typeof zPluginPromotionRuleItemValues>;

/**
 * Frontend options interface.
 * This schema extracts the `value` field from each property of the base schema.
 *
 * @note The backend will only sent the `value` for each option to save bandwidth.
 */
export const zPluginOptionsFrontend = transformSchema(zBasePluginOptions);
export type PluginOptionsFrontend = z.infer<typeof zPluginOptionsFrontend>;

/**
 * Window object: PluginAdminConfig
 *
 * @deprecated This is deprecated and should not be used.
 */
export type PluginAdminConfig = {
	nonce: string;
	nonceTimestamp: string;
	ajaxUrl: string;
	siteName: string;
};

// WC Settings: Window object.

export type WooCommerceSiteCurrency = {
	// The ISO code for the currency.
	code: string;
	// The precision (decimal places).
	precision: number;
	// The symbol for the currency (eg '$')
	symbol: string;
	// The position for the symbol ('left', or 'right')
	symbolPosition: SymbolPosition;
	// The string used for the decimal separator.
	decimalSeparator: string;
	// The string used for the thousands separator.
	thousandSeparator: string;
	// The format string use for displaying an amount in this currency.
	priceFormat: string;
};

type SymbolPosition = "left" | "left_space" | "right" | "right_space";

export type WooCommerceSiteLocale = {
	// The locale string for the current site.
	siteLocale: string;
	// The locale string for the current user.
	userLocale: string;
	// An array of short weekday strings in the current user's locale.
	weekdaysShort: string[];
};

type wcConfigPage =
	| {
			id: number;
			title: string;
			permalink: null | string;
	  }
	| undefined;

export type IWooSettings = {
	adminUrl: string;
	countries: Record<string, string> | never[];
	currency: WooCommerceSiteCurrency;
	currentUserIsAdmin: boolean;
	homeUrl: string;
	displayCartPricesIncludingTax: boolean;
	locale: WooCommerceSiteLocale;
	placeholderImgSrc: string;
	productsSettings: {
		cartRedirectAfterAdd: boolean;
	};
	canUserRegister: boolean;
	siteTitle: string;
	storePages: {
		myaccount: wcConfigPage;
		shop: wcConfigPage;
		cart: wcConfigPage;
		checkout: wcConfigPage;
		privacy: wcConfigPage;
		terms: wcConfigPage;
		leat_dashboard: wcConfigPage;
	};
	wcAssetUrl: string;
	wcVersion: string;
	wpLoginUrl: string;
	wpVersion: string;
	shippingCalculatorEnabled: boolean;
	taxesEnabled: boolean;
	couponsEnabled: boolean;
	shippingEnabled: boolean;
	showCartPricesIncTax: boolean;
	taxTotalDisplayItemized: boolean;
	countryTaxOrVat: string;
	endpoints: {
		"order-received": {
			active: boolean;
		};
	};
};
