<?php

namespace Leat;

use Leat\Api\Connection;
use Leat\Utils\Logger;
use Piggy\Api\Models\WebhookSubscriptions\WebhookSubscription;

class WebhookManager
{
	private const WEBHOOK_PREFIX = 'WordPress: ';
	private const LAST_SYNC_OPTION = 'leat_webhooks_last_sync';
	private const SYNC_INTERVAL = 24 * 60 * 60;

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var Logger
	 */
	private $logger;

	private const REQUIRED_WEBHOOKS = [
		'voucher_updated'  => [
			'name'       => 'WordPress: Voucher Updated',
			'event_type' => 'voucher_updated',
		],
		'voucher_created'  => [
			'name'       => 'WordPress: Voucher Created',
			'event_type' => 'voucher_created',
		],
		'voucher_redeemed' => [
			'name'       => 'WordPress: Voucher Redeemed',
			'event_type' => 'voucher_redeemed',
		],
		'contact_updated'  => [
			'name'       => 'WordPress: Contact Updated',
			'event_type' => 'contact_updated',
			'attributes' => ['email', 'firstname', 'lastname'],
		],
	];

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->logger     = new Logger();
	}

	/**
	 * Initialize the webhook manager
	 */
	public function init(): void
	{
		// Register activation hook
		register_activation_hook(LEAT_URL, [$this, 'install_webhooks']);

		// Register daily cron event for periodic checks
		add_action('leat_daily_webhook_check', [$this, 'check_webhooks']);

		// Schedule the cron event if not already scheduled
		if (!wp_next_scheduled('leat_daily_webhook_check')) {
			wp_schedule_event(time(), 'daily', 'leat_daily_webhook_check');
		}
	}

	/**
	 * Install webhooks on plugin activation
	 */
	public function install_webhooks(): void
	{
		$this->sync_webhooks();
	}

	/**
	 * Periodic check of webhooks
	 */
	public function check_webhooks(): void
	{
		$lastSync = get_option(self::LAST_SYNC_OPTION, 0);
		$now = time();

		// Only sync if enough time has passed since last sync
		if (($now - $lastSync) >= self::SYNC_INTERVAL) {
			$this->sync_webhooks();
		}
	}

	/**
	 * Sync webhooks with Leat API
	 */
	public function sync_webhooks(): void
	{
		try {
			$current_site_url = defined('WP_DEBUG') && defined('LEAT_WEBHOOK_URL')
				? LEAT_WEBHOOK_URL
				: get_site_url();

			$current_webhook_url = trailingslashit($current_site_url) . 'wp-json/leat/private/webhooks';

			$client = $this->connection->init_client();
			if (!$client) {
				return;
			}

			// Check existing webhooks first
			$existing_webhooks = WebhookSubscription::list();
			$needs_reinstall = false;

			// Check if any webhook URL doesn't match current URL
			foreach ($existing_webhooks as $webhook) {
				if (strpos($webhook->getName(), self::WEBHOOK_PREFIX) === 0) {
					if ($webhook->getUrl() !== $current_webhook_url) {
						$needs_reinstall = true;
						break;
					}
				}
			}

			$result = false;
			if ($needs_reinstall) {
				$this->logger->debug('Domain changed, reinstalling webhooks');
				// Delete all existing WordPress webhooks
				foreach ($existing_webhooks as $webhook) {
					if (strpos($webhook->getName(), self::WEBHOOK_PREFIX) === 0) {
						WebhookSubscription::delete($webhook->getUuid());
					}
				}
				$result = $this->ensure_webhooks_installed();
			} else {
				$result = $this->ensure_webhooks_installed();
			}

			if ($result) {
				update_option(self::LAST_SYNC_OPTION, time());
				$this->logger->debug('Webhook sync completed successfully');
			} else {
				$this->logger->error('Webhook sync failed');
			}
		} catch (\Exception $e) {
			$this->logger->error('Leat webhook sync failed: ' . $e->getMessage());
		}
	}

	/**
	 * Clean up when plugin is deactivated
	 */
	public function cleanup(): void
	{
		// Remove scheduled cron event
		wp_clear_scheduled_hook('leat_daily_webhook_check');

		// Clean up options
		delete_option(self::LAST_SYNC_OPTION);
	}

	public function ensure_webhooks_installed()
	{
		$client = $this->connection->init_client();

		// If no client, we don't have an API key, so we can't install webhooks
		if (! $client) {
			return false;
		}

		$site_url = defined('WP_DEBUG') && defined('LEAT_WEBHOOK_URL')
			? LEAT_WEBHOOK_URL
			: get_site_url();

		$webhook_url = trailingslashit($site_url) . 'wp-json/leat/private/webhooks';

		// Generate or get existing webhook secret
		$webhook_secret = get_option('leat_webhook_secret');
		if (!$webhook_secret) {
			$webhook_secret = wp_generate_password(32, false);
			update_option('leat_webhook_secret', $webhook_secret);
		}

		if (! $client) {
			return false;
		}

		try {
			$this->logger->debug('Attempting to list existing webhooks');

			$existing_webhooks = WebhookSubscription::list();

			// Cleans up any webhooks that are not in our required list
			foreach ($existing_webhooks as $existing_webhook) {
				if (strpos($existing_webhook->getName(), self::WEBHOOK_PREFIX) === 0) {
					$event_type  = $existing_webhook->getEventType();
					$is_required = false;
					foreach (self::REQUIRED_WEBHOOKS as $webhook_config) {
						if ($webhook_config['event_type'] === $event_type) {
							$is_required = true;
							break;
						}
					}
					if (! $is_required) {
						WebhookSubscription::delete($existing_webhook->getUuid());
					}
				}
			}

			// Adds any missing webhooks
			foreach (self::REQUIRED_WEBHOOKS as $key => $webhook_config) {
				try {
					$exists = false;
					foreach ($existing_webhooks as $existing_webhook) {
						if (
							$existing_webhook->getEventType() === $webhook_config['event_type']
							&& strpos($existing_webhook->getName(), self::WEBHOOK_PREFIX) === 0
						) {
							$exists = true;
							// Update webhook if the URL is wrong.
							if ($existing_webhook->getUrl() !== $webhook_url) {
								$updateData = [
									'url'    => $webhook_url,
									'status' => 'ACTIVE',
									'secret' => $webhook_secret,
								];

								// Add attributes if they exist in the config
								if (isset($webhook_config['attributes'])) {
									$updateData['attributes'] = $webhook_config['attributes'];
								}

								WebhookSubscription::update(
									$existing_webhook->getUuid(),
									$updateData
								);
							}
							break;
						}
					}

					if (! $exists) {
						$webhookData = [
							'name'       => $webhook_config['name'],
							'event_type' => $webhook_config['event_type'],
							'url'        => $webhook_url,
							'status'     => 'ACTIVE',
							'secret'     => $webhook_secret,
						];

						if (isset($webhook_config['attributes'])) {
							$webhookData['attributes'] = $webhook_config['attributes'];
						}

						WebhookSubscription::create($webhookData);

						$this->logger->debug('Created webhook ' . $webhook_config['event_type'] . ' with URL ' . $webhook_url);
					}
				} catch (\Exception $e) {
					$this->logger->error('Failed to process webhook ' . $webhook_config['event_type'] . ': ' . $e->getMessage());
					continue;
				}
			}

			return true;
		} catch (\Piggy\Api\Exceptions\Error $e) {
			$this->logger->error('API Error details:', wp_json_encode($e->getErrors()));
			return false;
		} catch (\TypeError $e) {
			$this->logger->error('Type Error in SDK:', [
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine()
			]);
			return false;
		} catch (\Throwable $th) {
			$this->logger->error('Unexpected error while listing webhooks:', [
				'message' => $th->getMessage(),
				'type' => get_class($th)
			]);
			return false;
		}
	}

	public function handle_webhook($event_type, $data)
	{
		switch ($event_type) {
			case 'contact_updated':
				$this->handle_contact_updated($data);
				break;
			case 'voucher_updated':
				$this->handle_voucher_updated($data);
				break;
			case 'voucher_created':
				$this->handle_voucher_created($data);
				break;
			case 'voucher_deleted':
				$this->handle_voucher_deleted($data);
				break;
			case 'voucher_redeemed':
				$this->handle_voucher_redeemed($data);
				break;
		}
	}

	private function handle_voucher_updated($data)
	{
		if (empty($data['voucher'])) {
			$this->logger->error('Voucher data missing from webhook payload', [
				'data' => $data,
			]);
			return;
		}

		$voucher = $data['voucher'];

		do_action('leat_webhook_voucher_updated', $voucher);

		$this->logger->debug('Voucher updated webhook processed', [
			'voucher' => $voucher
		]);
	}

	private function handle_voucher_created($data)
	{
		if (empty($data['voucher'])) {
			$this->logger->error('Voucher data missing from webhook payload', [
				'data' => $data,
			]);
			return;
		}

		$voucher = $data['voucher'];

		do_action('leat_webhook_voucher_created', $voucher);

		$this->logger->debug('Voucher created webhook processed', [
			'voucher' => $voucher
		]);
	}

	private function handle_voucher_deleted($data)
	{
		if (empty($data['voucher'])) {
			$this->logger->error('Voucher data missing from webhook payload', [
				'data' => $data,
			]);
			return;
		}

		$voucher = $data['voucher'];

		do_action('leat_webhook_voucher_deleted', $voucher);

		$this->logger->debug('Voucher deleted webhook processed', [
			'voucher' => $voucher
		]);
	}

	private function handle_contact_updated($data)
	{
		if (empty($data['contact'])) {
			$this->logger->error('Contact data missing from webhook payload');
			return;
		}

		$contact = $data['contact'];

		do_action('leat_webhook_contact_updated', $contact);

		$this->logger->debug('Contact updated webhook processed', [
			'contact_uuid' => $contact['uuid'],
			'contact_email' => $contact['email']
		]);
	}

	private function handle_voucher_redeemed($data)
	{
		if (empty($data['voucher'])) {
			$this->logger->error('Voucher data missing from webhook payload', [
				'data' => $data,
			]);
			return;
		}

		$voucher = $data['voucher'];

		do_action('leat_webhook_voucher_redeemed', $voucher);

		$this->logger->debug('Voucher redeemed webhook processed', [
			'voucher' => $voucher
		]);
	}

	/**
	 * Returns the required webhooks for the plugin
	 *
	 * @return array List of webhooks with their details
	 */
	public function get_required_webhooks(): array
	{
		return self::REQUIRED_WEBHOOKS;
	}

	/**
	 * Get the current list of WordPress webhooks
	 *
	 * @return array List of webhooks with their details
	 */
	public function get_webhooks(): array
	{
		try {
			$client = $this->connection->init_client();
			if (!$client) {
				return [];
			}

			$existing_webhooks = WebhookSubscription::list();
			$wordpress_webhooks = [];

			foreach ($existing_webhooks as $webhook) {
				if (strpos($webhook->getName(), self::WEBHOOK_PREFIX) === 0) {
					$wordpress_webhooks[] = [
						'id' => $webhook->getUuid(),
						'name' => $webhook->getName(),
						'event_type' => $webhook->getEventType(),
						'url' => $webhook->getUrl(),
						'status' => $webhook->getStatus(),
					];
				}
			}

			return $wordpress_webhooks;
		} catch (\Exception $e) {
			$this->logger->error('Failed to get webhooks: ' . $e->getMessage());
			return [];
		}
	}
}
