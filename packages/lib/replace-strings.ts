export type ReplaceStringsObj = {
	[key: string]: string;
}[];

export const replaceStrings = (text: string, obj: ReplaceStringsObj) => {
	return obj.reduce((acc, replacementObj) => {
		const key = Object.keys(replacementObj)[0];
		const value = replacementObj[key];
		if (acc.includes(key)) {
			return acc.replace(key, value);
		}
		return acc;
	}, text);
};
