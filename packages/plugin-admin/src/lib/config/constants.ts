import { __ } from "@wordpress/i18n";
import type { HeaderLinkProps } from "$lib/types";

export const headerLinks = [
	{
		href: "/",
		label: __("General", "leat"),
		type: "link",
	},
	{
		href: "/loyalty-program",
		label: __("Loyalty Program", "leat"),
		type: "link",
	},
	{
		href: "/promotions",
		label: __("Promotions", "leat"),
		type: "link",
	},
	{
		href: "/dashboard",
		label: __("Customer Dashboard settings", "leat"),
		type: "link",
	},
] as HeaderLinkProps[];
