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
        // Nothing to initialize
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @return string[]
     */
    public function get_script_handles(): array
    {
        return ['leat-giftcard-coupon'];
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
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('leat_check_giftcard_balance'),
            'checking_text' => __('Checking gift card balance...', 'leat-crm'),
            'balance_text' => __('Gift card balance: ', 'leat-crm'),
            'error_text' => __('Not a valid gift card or error checking balance.', 'leat-crm'),
        ];
    }
}
