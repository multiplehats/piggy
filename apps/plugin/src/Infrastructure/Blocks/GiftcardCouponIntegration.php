<?php

namespace Leat\Infrastructure\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Leat\Domain\Package;

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
     * Constructor.
     *
     * @param Package $package The package instance.
     */
    public function __construct(Package $package)
    {
        $this->package = $package;
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
        // Only add styles on cart/checkout pages, with proper function checks
        if (function_exists('is_cart') && function_exists('is_checkout') && (!is_cart() && !is_checkout()) && !has_block('woocommerce/checkout') && !has_block('woocommerce/cart')) {
            return;
        }

        // Register the giftcard checkout integration script
        $asset_file_path = $this->package->get_path('assets/js/frontend/giftcard-checkout-integration.asset.php');
        $asset = file_exists($asset_file_path) ? include($asset_file_path) : ['dependencies' => [], 'version' => '1.0.0'];

        wp_register_script(
            'leat-giftcard-checkout-integration',
            $this->package->get_url('assets/js/frontend/giftcard-checkout-integration.js'),
            $asset['dependencies'] ?? [],
            $asset['version'] ?? $this->package->get_version(),
            true
        );

        // Localize the script with required data
        wp_localize_script(
            'leat-giftcard-checkout-integration',
            'leatGiftCardConfig',
            $this->get_script_data()
        );

        // Enqueue the CSS
        wp_enqueue_style(
            'leat-giftcard-styles',
            $this->package->get_url('assets/js/css/giftcard-checkout-integration.css'),
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
            'checkingText' => __('Checking gift card balance...', 'leat-crm'),
            'balanceText' => __('Gift card balance: ', 'leat-crm'),
            'errorText' => __('Not a valid gift card or error checking balance.', 'leat-crm'),
        ];
    }
}
