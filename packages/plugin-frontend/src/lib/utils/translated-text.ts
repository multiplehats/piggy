import { replaceStrings } from "@leat/lib";
import { currentLanguage } from "$lib/modules/settings";

export function getTranslatedText(tx: Record<string, string> | null): string {
	if (!tx) return "";

	// Try to get the translation for the current language
	if (tx[currentLanguage]) {
		return tx[currentLanguage];
	}

	// If not found, try to get the 'default' translation
	if (tx.default) {
		return tx.default;
	}

	// If 'default' is not available, fall back to the first available translation
	return tx[Object.keys(tx)[0]] ?? "";
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
