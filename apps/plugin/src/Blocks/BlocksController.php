<?php

namespace Leat\Blocks;

use Leat\Api\Connection;
use Leat\Settings;
use Leat\Utils\Logger;
use Leat\Assets\Api as AssetApi;

class BlocksController
{
    /**
     * @var AssetApi
     */
    private $asset_api;

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

    public function __construct(AssetApi $asset_api, Connection $connection, Settings $settings)
    {
        $this->asset_api = $asset_api;
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

        $integration_registry->register(new GiftcardRecipientBlock\Block(
            $this->asset_api,
            $this->connection,
            $this->settings
        ));
    }
}
