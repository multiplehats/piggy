<?php

namespace Leat;

class RedirectController {
    public function init() {
        add_action('admin_init', array($this, 'maybe_redirect_to_onboarding'));
    }

    public function maybe_redirect_to_onboarding() {
        // Check for nonce if this is a form submission
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(wp_unslash($_REQUEST['_wpnonce']), 'leat_redirect')) {
            return;
        }

        $api_key = get_option('leat_api_key', null);
        $first_activation = get_option('leat_first_activation', false);

        // Check if we're on the Leat plugin page
        if (isset($_GET['page']) && $_GET['page'] === 'leat' &&
            ($first_activation === false || $api_key === null || $api_key === '')) {
            wp_redirect(admin_url('admin.php?page=leat#/onboarding'));
            exit;
        }
    }
}