<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Utils\Logger;
use Piggy\Api\Models\WebhookSubscriptions\WebhookSubscription;

class WebhookManager {
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Logger
     */
    private $logger;

    private const REQUIRED_WEBHOOKS = [
        'voucher_created' => [
            'name' => 'Voucher Created',
            'event_type' => 'voucher_created',
        ],
        'voucher_updated' => [
            'name' => 'Voucher Updated',
            'event_type' => 'voucher_updated',
        ],
        'voucher_redeemed' => [
            'name' => 'Voucher Redeemed',
            'event_type' => 'voucher_redeemed',
        ]
    ];

    public function __construct(Connection $connection) {
        $this->connection = $connection;
        $this->logger = new Logger();
    }

    public function ensure_webhooks_installed() {
        if (!$this->connection->init_client()) {
            $this->logger->error('Failed to initialize API client for webhook installation');
            return false;
        }

        try {
            $existing_webhooks = WebhookSubscription::list();
            $site_url = get_site_url();
            $webhook_url = trailingslashit($site_url) . 'wp-json/leat/v1/webhooks';

            foreach (self::REQUIRED_WEBHOOKS as $key => $webhook_config) {
                $exists = false;
                foreach ($existing_webhooks as $existing_webhook) {
                    if ($existing_webhook->getEventType() === $webhook_config['event_type']) {
                        $exists = true;
                        // Update webhook if URL is wrong
                        if ($existing_webhook->getUrl() !== $webhook_url) {
                            WebhookSubscription::update($existing_webhook->getUuid(), [
                                'url' => $webhook_url,
                                'status' => 'ACTIVE'
                            ]);
                        }
                        break;
                    }
                }

                if (!$exists) {
                    WebhookSubscription::create([
                        'name' => $webhook_config['name'],
                        'event_type' => $webhook_config['event_type'],
                        'url' => $webhook_url,
                        'status' => 'ACTIVE'
                    ]);
                }
            }

            return true;
        } catch (\Throwable $th) {
            $this->logger->error('Failed to install webhooks: ' . $th->getMessage());
            return false;
        }
    }

    public function handle_webhook($event_type, $data) {
        switch ($event_type) {
            case 'voucher_created':
            case 'voucher_updated':
            case 'voucher_redeemed':
                $this->handle_voucher_webhook($event_type, $data);
                break;
        }
    }

    private function handle_voucher_webhook($event_type, $data) {
        $voucher_sync = new VoucherSync($this->connection);
        $contact_uuid = $data['contact_uuid'];
        $user = $this->connection->get_user_from_leat_uuid($contact_uuid);

        if (!$user) {
            $this->logger->error("No WordPress user found for contact UUID: $contact_uuid");
            return;
        }

        // Fetch updated voucher data and update user meta
        try {
            $vouchers = \Piggy\Api\Models\Vouchers\Voucher::list([
                'contact_uuid' => $contact_uuid,
                'promotion_uuid' => $data['promotion_uuid'],
                'limit' => 100,
                'page' => 1
            ]);

            if (!empty($vouchers)) {
                $voucher_sync->update_user_vouchers($user->ID, $vouchers);
            }
        } catch (\Throwable $th) {
            $this->logger->error("Error processing voucher webhook: " . $th->getMessage());
        }
    }
}