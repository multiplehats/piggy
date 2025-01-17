import { __ } from "@wordpress/i18n";
import type { HeaderLinkProps } from "$lib/types";

export const headerLinks = [
	{
		href: "/",
		label: __("General", "leat-crm"),
		type: "link",
	},
	{
		href: "/loyalty-program",
		label: __("Loyalty Program", "leat-crm"),
		type: "link",
	},
	{
		href: "/promotions",
		label: __("Promotions", "leat"),
		type: "link",
	},
	{
		href: "/dashboard",
		label: __("Customer Dashboard", "leat-crm"),
		type: "link",
	},
] as HeaderLinkProps[];
