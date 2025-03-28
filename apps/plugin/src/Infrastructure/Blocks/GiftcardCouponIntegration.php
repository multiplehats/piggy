<?php

namespace Leat\Infrastructure\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Leat\Domain\Package;
use Leat\Settings;

/**
 * Class GiftcardCouponIntegration
 *
 * Adds gift card balance checking functionality to WooCommerce Blocks.
 */
class GiftcardCouponIntegration implements IntegrationInterface
{
    /**
     * Package instance for accessing plugin metadata.
     *
     * @var Package
     */
    private $package;

    /**
     * Settings instance for accessing plugin settings.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param Package $package The package instance.
     * @param Settings $settings The settings instance.
     */
    public function __construct(Package $package, Settings $settings)
    {
        $this->package = $package;
        $this->settings = $settings;
    }

    /**
     * The name of the integration.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'leat-giftcard-coupon';
    }

    /**
     * When called invokes any initialization for the integration.
     */
    public function initialize(): void
    {
        // Register script and styles
        add_action('wp_enqueue_scripts', [$this, 'register_scripts_and_styles'], 10);

        // Make sure our script is enqueued when blocks are used
        add_action('enqueue_block_assets', [$this, 'enqueue_block_scripts']);
    }

    /**
     * Register scripts and styles for the integration
     */
    public function register_scripts_and_styles(): void
    {
        if (function_exists('is_cart') && function_exists('is_checkout') && (!is_cart() && !is_checkout()) && !has_block('woocommerce/checkout') && !has_block('woocommerce/cart')) {
            return;
        }

        $asset_file_path = $this->package->get_path('dist/frontend/blocks/giftcard-checkout-integration.asset.php');
        $asset = file_exists($asset_file_path) ? include($asset_file_path) : ['dependencies' => [], 'version' => '1.0.0'];

        $js_files = glob($this->package->get_path('dist/frontend/blocks/giftcard-checkout-integration.*.js'));
        $latest_js = !empty($js_files) ? basename(end($js_files)) : 'giftcard-checkout-integration.js';

        // Ensure we have the required WordPress dependencies
        $dependencies = array_merge(['wp-element', 'wp-plugins', 'wp-i18n'], $asset['dependencies'] ?? []);

        wp_register_script(
            'leat-giftcard-checkout-integration',
            $this->package->get_url('dist/frontend/blocks/' . $latest_js),
            $dependencies,
            $asset['version'] ?? $this->package->get_version(),
            true
        );

        // Add a check to ensure wp.plugins is available
        wp_add_inline_script(
            'leat-giftcard-checkout-integration',
            'if (typeof wp === "undefined" || typeof wp.plugins === "undefined") { console.error("wp.plugins is not available"); }',
            'before'
        );

        wp_localize_script(
            'leat-giftcard-checkout-integration',
            'leatGiftCardConfig',
            $this->get_script_data()
        );

        $css_files = glob($this->package->get_path('dist/frontend/blocks/gift-card-styles.*.css'));
        $latest_css = !empty($css_files) ? basename(end($css_files)) : 'gift-card-styles.css';

        // Enqueue the styles
        wp_enqueue_style(
            'leat-giftcard-styles',
            $this->package->get_url('dist/frontend/blocks/' . $latest_css),
            ['woocommerce-inline'],
            $this->package->get_version()
        );
    }

    /**
     * Ensure scripts are loaded when blocks are used
     */
    public function enqueue_block_scripts(): void
    {
        if (has_block('woocommerce/checkout') || has_block('woocommerce/cart')) {
            wp_enqueue_script('leat-giftcard-checkout-integration');
        }
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @return string[]
     */
    public function get_script_handles(): array
    {
        return ['leat-giftcard-checkout-integration'];
    }

    /**
     * Returns an array of script handles to enqueue in the admin context.
     *
     * @return string[]
     */
    public function get_script_handles_for_admin(): array
    {
        return [];
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles(): array
    {
        return [];
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data(): array
    {
        return [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('leat_check_giftcard_balance'),
            'checkingText' => $this->settings->get_setting_value_by_id('giftcard_checking_balance_text'),
            'balanceText' => $this->settings->get_setting_value_by_id('giftcard_balance_text'),
            'errorText' => __('Not a valid gift card or error checking balance.', 'leat-crm'),
        ];
    }
}
