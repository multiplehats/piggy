<?php

namespace PiggyWP;

class RedirectController {
    public function init() {
        add_action('admin_init', array($this, 'maybe_redirect_to_onboarding'));
    }

    public function maybe_redirect_to_onboarding() {
        $screen = get_current_screen();
        $api_key = get_option('piggy_api_key', null);
        $first_activation = get_option('piggy_first_activation', false);

        // Check if we're on the main plugin settings page
        if ($screen->id === 'toplevel_page_piggy' && ($first_activation === false || $api_key === null || $api_key === '')) {
            wp_redirect(admin_url('admin.php?page=piggy#/onboarding'));
            exit;
        }
    }
}