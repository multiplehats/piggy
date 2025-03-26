export type StringReplacement =
	| `{{${string}}}`
	| `{{ ${string} }}`
	| `{{${string} }}`
	| `{{ ${string}}}`;

export type StringReplacementConfig = {
	[key in StringReplacement]?: string;
}[];

export function replaceStrings(text: string, obj: StringReplacementConfig) {
	return obj.reduce((acc, replacementObj) => {
		Object.entries(replacementObj).forEach(([key, value]) => {
			const regex = new RegExp(`{{\\s*${key.slice(2, -2).trim()}\\s*}}`, "g");
			acc = acc.replace(regex, value ?? "");
		});
		return acc;
	}, text);
}

export const currentLanguage = window?.leatMiddlewareConfig?.currentLanguage || "en_US";

export function getTranslatedText(tx: Record<string, string> | null): string {
	if (!tx) return "";

	// Try to get the translation for the current language
	if (tx[currentLanguage]) {
		return tx[currentLanguage] ?? "";
	}

	// If not found, try to get the 'default' translation
	if (tx.default) {
		return tx.default;
	}

	// If 'default' is not available, fall back to the first available translation
	return tx[Object.keys(tx)[0] ?? ""] ?? "";
}

export function getSpendRuleLabel(
	text: string,
	credits: number | null,
	creditsName: string,
	discount: number | null,
	discountType: "percentage" | "fixed"
) {
	if (!text) return "";

	const get_discount_type = () => {
		if (discountType === "percentage") {
			return `${discount}%`;
		} else if (discountType === "fixed") {
			const currency = window.leatWcSettings.currency.symbol;

			return `${currency}${discount}`;
		}

		return `${discount}`;
	};

	return replaceStrings(text, [
		{
			"{{ credits_currency }}": creditsName ?? "",
			"{{ credits }}": credits?.toString() ?? "0",
			"{{ discount }}": get_discount_type(),
		},
	]);
}
