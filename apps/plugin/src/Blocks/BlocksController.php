<?php

namespace Leat\Blocks;

use Leat\Api\Connection;
use Leat\Settings;
use Leat\Utils\Logger;

class BlocksController
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Connection $connection, Settings $settings)
    {
        $this->connection = $connection;
        $this->settings = $settings;
        $this->logger = new Logger();
    }

    public function init(): void
    {
        add_action('woocommerce_blocks_loaded', [$this, 'register_block_integrations']);
    }

    public function register_block_integrations(): void
    {
        if (!class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry')) {
            return;
        }

        $integration_registry = new \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry();

        // Register the gift card recipient block integration
        $integration_registry->register(new GiftCardRecipientBlock(
            $this->connection,
            $this->settings
        ));
    }
}
