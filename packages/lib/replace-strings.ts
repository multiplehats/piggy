export type StringReplacement =
	| `{{${string}}}`
	| `{{ ${string} }}`
	| `{{${string} }}`
	| `{{ ${string}}}`;

export type StringReplacementConfig = {
	[key in StringReplacement]?: string;
}[];

export const replaceStrings = (text: string, obj: StringReplacementConfig) => {
	return obj.reduce((acc, replacementObj) => {
		Object.entries(replacementObj).forEach(([key, value]) => {
			const regex = new RegExp(`{{\\s*${key.replace(/[{}]/g, '')}\\s*}}`, 'g');
			acc = acc.replace(regex, value ?? '');
		});
		return acc;
	}, text);
};
