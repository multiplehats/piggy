<?php

namespace PiggyWP;

class RedirectController {
    public function init() {
        add_action('admin_init', array($this, 'maybe_redirect_to_onboarding'));
    }

    public function maybe_redirect_to_onboarding() {
        // Remove the $screen variable as it's not reliable in this context
        $api_key = get_option('piggy_api_key', null);
        $first_activation = get_option('piggy_first_activation', false);

        // Check if we're on the Piggy plugin page
        if (isset($_GET['page']) && $_GET['page'] === 'piggy' &&
            ($first_activation === false || $api_key === null || $api_key === '')) {
            wp_redirect(admin_url('admin.php?page=piggy#/onboarding'));
            exit;
        }
    }
}