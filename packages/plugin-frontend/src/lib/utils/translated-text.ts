import { currentLanguage } from '$lib/modules/settings';

export function getTranslatedText(tx: Record<string, string> | null): string {
	return tx?.[currentLanguage] ?? tx?.[Object.keys(tx)[0]] ?? '';
}
