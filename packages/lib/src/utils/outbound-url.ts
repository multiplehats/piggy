/**
 * Create outbound URL with UTM params and custom params.
 */
export default function outboundUrl({
	url,
	source,
	campaign,
	medium,
	params,
}: {
	url: string;
	source: string;
	campaign: string;
	medium: string;
	params?: Record<string, string>;
}) {
	const outboundLink = new URL(url);

	// Create object from opts
	const urlParams = {
		utm_source: source,
		utm_medium: medium,
		utm_campaign: campaign,
		...params,
	};
	type UrlParamsKeys = keyof typeof urlParams;

	// Add params to URL
	Object.keys(urlParams).forEach((key) => {
		if (urlParams[key as UrlParamsKeys]) {
			outboundLink.searchParams.append(key, urlParams[key as UrlParamsKeys]);
		}
	});

	return outboundLink.toString();
}
