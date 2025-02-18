<?php

namespace Leat\WCBlocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class GiftCardRecipientBlock implements IntegrationInterface
{
    /**
     * The name of the integration.
     */
    public function get_name(): string
    {
        return 'leat-giftcard-recipient';
    }

    /**
     * When called invokes any initialization/setup for the integration.
     */
    public function initialize(): void
    {
        $this->register_assets();
        $this->register_editor_blocks();
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     */
    public function get_script_handles(): array
    {
        return ['leat-giftcard-recipient-block-frontend'];
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     */
    public function get_editor_script_handles(): array
    {
        return ['leat-giftcard-recipient-block-editor'];
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     */
    public function get_script_data(): array
    {
        return [
            'data-attribute' => 'value',
        ];
    }

    protected function register_assets(): void
    {
        $script_path = 'blocks/gift-card-recipient/frontend.js';
        $style_path = 'blocks/gift-card-recipient/style.css';

        wp_register_script(
            'leat-giftcard-recipient-block-frontend',
            plugins_url($script_path, LEAT_PLUGIN_FILE),
            ['@woocommerce/blocks-checkout'],
            LEAT_VERSION,
            true
        );

        wp_register_style(
            'leat-giftcard-recipient-block',
            plugins_url($style_path, LEAT_PLUGIN_FILE),
            [],
            LEAT_VERSION
        );
    }
}
