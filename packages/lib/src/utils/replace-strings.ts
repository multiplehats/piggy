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
