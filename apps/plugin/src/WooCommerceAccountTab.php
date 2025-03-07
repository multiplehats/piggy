<?php

namespace Leat;

use Leat\Utils\TranslatedText;

/**
 * Handles WooCommerce account integration for Leat.
 * Adds and manages the Leat dashboard tab in the WooCommerce My Account area.
 */
class WooCommerceAccountTab
{
    /**
     * @var Settings
     */
    protected $settings;

    /**
     * Initialize dependencies.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Initialize hooks.
     */
    public function init()
    {
        // Register activation hook
        register_activation_hook(LEAT_URL, [$this, 'set_flush_rewrite_rules']);

        // Add a new tab to the My Account page
        add_filter('woocommerce_account_menu_items', array($this, 'add_leat_dashboard_tab'));

        // Add endpoint for the new tab
        add_action('init', array($this, 'add_leat_dashboard_endpoint'));

        // Add content to the new tab
        add_action('woocommerce_account_leat-dashboard_endpoint', array($this, 'leat_dashboard_content'));

        // Redirect after login
        add_filter('woocommerce_login_redirect', array($this, 'redirect_after_login'), 10, 2);
    }

    /**
     * Flush rewrite rules if needed.
     */
    public function set_flush_rewrite_rules()
    {
        update_option('leat_flush_rewrite_rules', 'yes');
    }

    /**
     * Add Leat Dashboard endpoint.
     */
    public function add_leat_dashboard_endpoint()
    {
        add_rewrite_endpoint('leat-dashboard', EP_ROOT | EP_PAGES);

        // Check if we need to flush rewrite rules
        if (get_option('leat_flush_rewrite_rules', 'no') === 'yes') {
            flush_rewrite_rules();
            update_option('leat_flush_rewrite_rules', 'no');
        }
    }

    /**
     * Add Leat Dashboard tab to My Account menu.
     *
     * @param array $items Account menu items.
     * @return array
     */
    public function add_leat_dashboard_tab($items)
    {
        $title_setting = $this->settings->get_setting_value_by_id('dashboard_myaccount_title');
        $title_text = TranslatedText::get_text($title_setting);

        // Get the position where we want to insert our tab (after Dashboard)
        $dashboard_position = array_search('dashboard', array_keys($items), true);

        // Insert our item after the Dashboard
        $new_items = array_slice($items, 0, $dashboard_position + 1, true);
        $new_items['leat-dashboard'] = $title_text;
        $new_items += array_slice($items, $dashboard_position + 1, count($items), true);

        return $new_items;
    }

    /**
     * Add content to the Leat Dashboard tab.
     */
    public function leat_dashboard_content()
    {
        // Output the customer dashboard shortcode
        echo do_shortcode('[leat_dashboard]');
    }

    /**
     * Redirect users to the Leat Dashboard tab after login.
     *
     * @param string $redirect Default redirect URL.
     * @param \WP_User $user Logged in user.
     * @return string
     */
    public function redirect_after_login($redirect, $user)
    {
        // Get the My Account page URL
        $myaccount_page_id = wc_get_page_id('myaccount');

        if ($myaccount_page_id > 0) {
            // Redirect to the Leat Dashboard tab
            $redirect = wc_get_endpoint_url('leat-dashboard', '', get_permalink($myaccount_page_id));
        }

        return $redirect;
    }
}
