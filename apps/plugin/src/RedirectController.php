<?php

namespace Leat;

class RedirectController {
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ) );
	}

	public function maybe_redirect_to_onboarding() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'leat_redirect' ) ) {
			return;
		}

		$api_key          = get_option( 'leat_api_key', null );
		$first_activation = get_option( 'leat_first_activation', false );

		if ( isset( $_GET['page'] ) && 'leat' === $_GET['page'] && ( false === $first_activation || null === $api_key || '' === $api_key ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=leat#/onboarding' ) );
			exit;
		}
	}
}
