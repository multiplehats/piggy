import { wpApiClient } from '@piggy/lib';

export const api = wpApiClient({
	ajaxUrl: window.piggyAdminConfig.ajaxUrl,
	nonce: window.piggyAdminConfig.nonce
});
