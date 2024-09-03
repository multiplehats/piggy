import { currentLanguage } from '$lib/modules/settings';
import { replaceStrings } from '@piggy/lib';

export function getTranslatedText(tx: Record<string, string> | null): string {
	return tx?.[currentLanguage] ?? tx?.[Object.keys(tx)[0]] ?? '';
}

export function getSpendRuleLabel(
	text: string,
	credits: number | null,
	creditsName: string,
	discount: number | null
) {
	if (!text) return '';

	return replaceStrings(text, [
		{
			'{{ credits_currency }}': creditsName ?? '',
			'{{ credits }}': credits?.toString() ?? '0',
			'{{ discount }}': discount?.toString() ?? '0'
		}
	]);
}
