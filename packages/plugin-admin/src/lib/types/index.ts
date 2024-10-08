export type LinkTypes = "link" | "external";

export type HeaderLinkProps = {
	label: string;
	type: LinkTypes;
	target?: string;
	href?: string;
};

export type AdminProduct = {
	id: number;
	name: string;
	categories: {
		id: number;
		name: string;
	};
};
