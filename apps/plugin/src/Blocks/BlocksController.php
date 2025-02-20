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
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function ($integration_registry) {
                $integration_registry->register(new GiftcardRecipientBlock\Block(
                    $this->connection,
                    $this->settings
                ));
            }
        );
    }
}
