import { get } from 'svelte/store';
import { replaceStrings } from '@piggy/lib';
import { currentLanguage, pluginSettings } from '../modules/settings';

export function replacePlaceholders(text?: string) {
	if (!text) return '';

	const settings = get(pluginSettings);
	const creditsName = settings?.credits_name?.[currentLanguage];
	const credits = window.piggyData.contact?.balance.credits;

	return replaceStrings(text, [
		{
			'{{credits__currency}}': creditsName ?? '',
			'{{credits}}': credits?.toString() ?? '0'
		}
	]);
}
