export type LinkTypes = 'link' | 'external';

export interface HeaderLinkProps {
	label: string;
	type: LinkTypes;
	target?: string;
	href?: string;
}

export interface AdminProduct {
	id: number;
	name: string;
	categories: {
		id: number;
		name: string;
	};
}
