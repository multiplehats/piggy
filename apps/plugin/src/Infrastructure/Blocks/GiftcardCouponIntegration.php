<?php

namespace Leat\Infrastructure\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class GiftcardCouponIntegration
 *
 * Adds gift card balance checking functionality to WooCommerce Blocks.
 */
class GiftcardCouponIntegration implements IntegrationInterface
{
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
        $asset_file = include(dirname(dirname(dirname(__DIR__))) . '/assets/js/frontend/giftcard-checkout-integration.asset.php');

        wp_register_script(
            'leat-giftcard-checkout-integration',
            plugins_url('assets/js/frontend/giftcard-checkout-integration.js', dirname(dirname(dirname(__DIR__))) . '/leat-crm.php'),
            $asset_file['dependencies'] ?? ['wp-element', 'wp-plugins', 'wc-blocks-checkout'],
            $asset_file['version'] ?? LEAT_VERSION,
            true
        );

        // Localize the script with required data
        wp_localize_script(
            'leat-giftcard-checkout-integration',
            'leatGiftCardConfig',
            $this->get_script_data()
        );

        // Add inline styles for both classic and block checkout
        wp_add_inline_style('woocommerce-inline', '
            .leat-giftcard-balance {
                margin-top: 5px;
                font-size: 0.9em;
                display: none;
            }
            .leat-giftcard-balance.success {
                color: green;
                display: block;
            }
            .leat-giftcard-balance.error {
                color: red;
            }
            .wc-block-components-totals-coupon__content .leat-giftcard-balance {
                margin-top: 0;
                margin-bottom: 8px;
            }
            .leat-giftcard-balances {
                margin-top: 10px;
                padding: 8px;
                background-color: #f8f8f8;
                border-radius: 4px;
            }
        ');
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
