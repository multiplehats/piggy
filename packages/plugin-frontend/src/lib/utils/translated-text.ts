import { currentLanguage } from '$lib/modules/settings';
import { replaceStrings } from '@piggy/lib';

export function getTranslatedText(tx: Record<string, string> | null): string {
	return tx?.[currentLanguage] ?? tx?.[Object.keys(tx)[0]] ?? '';
}

export function getSpendRuleLabel(
	text: string,
	credits: number | null,
	creditsName: string,
	discount: number | null,
	discountType: 'percentage' | 'fixed'
) {
	if (!text) return '';

	const getDiscountType = () => {
		if (discountType === 'percentage') {
			return `${discount}%`;
		} else if (discountType === 'fixed') {
			const currency = window.piggyWcSettings.currency.symbol;

			return `${currency}${discount}`;
		}

		return `${discount}`;
	};

	return replaceStrings(text, [
		{
			'{{ credits_currency }}': creditsName ?? '',
			'{{ credits }}': credits?.toString() ?? '0',
			'{{ discount }}': getDiscountType()
		}
	]);
}
