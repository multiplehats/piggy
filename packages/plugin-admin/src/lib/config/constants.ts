import { __ } from '@wordpress/i18n';
import type { HeaderLinkProps } from '$lib/types';

export const headerLinks = [
	{
		href: '/',
		label: __('Dashboard', 'piggy'),
		type: 'link'
	},
	{
		href: '/loyalty-program',
		label: __('Loyalty Program', 'piggy'),
		type: 'link'
	},
	{
		href: '/general',
		label: __('General', 'piggy'),
		type: 'link'
	}
] as HeaderLinkProps[];
