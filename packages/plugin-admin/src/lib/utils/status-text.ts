import { __ } from '@wordpress/i18n';

export function getStatusText(status: string) {
	if (status === 'publish') {
		return __('Active', 'piggy');
	} else if (status === 'draft') {
		return __('Inactive', 'piggy');
	}

	return status;
}
