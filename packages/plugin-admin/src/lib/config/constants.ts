import { __ } from '@wordpress/i18n';
import type { HeaderLinkProps } from '$lib/types';

export const headerLinks = [
	{
		href: '/',
		label: __('General', 'piggy'),
		type: 'link'
	},
	{
		href: '/dashboard',
		label: __('Dashboard', 'piggy'),
		type: 'link'
	},
	{
		href: '/loyalty-program',
		label: __('Loyalty Program', 'piggy'),
		type: 'link'
	}
] as HeaderLinkProps[];
