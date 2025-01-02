<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Utils\Logger;
use Piggy\Api\Models\WebhookSubscriptions\WebhookSubscription;

class WebhookManager {
	private const WEBHOOK_PREFIX = 'WordPress: ';

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var Logger
	 */
	private $logger;

	private const REQUIRED_WEBHOOKS = [
		'voucher_created'  => [
			'name'       => 'WordPress: Voucher Created',
			'event_type' => 'voucher_created',
		],
		'voucher_updated'  => [
			'name'       => 'WordPress: Voucher Updated',
			'event_type' => 'voucher_updated',
		],
		'voucher_redeemed' => [
			'name'       => 'WordPress: Voucher Redeemed',
			'event_type' => 'voucher_redeemed',
		],
		'contact_updated'  => [
			'name'       => 'WordPress: Contact Updated',
			'event_type' => 'contact_updated',
		],
	];


	public function __construct( Connection $connection ) {
		$this->connection = $connection;
		$this->logger     = new Logger();
	}

	public function ensure_webhooks_installed() {
		if ( ! $this->connection->init_client() ) {
			$this->logger->error( 'Failed to initialize API client for webhook installation' );
			return false;
		}

		try {
			$existing_webhooks = WebhookSubscription::list( [] );
			$site_url          = get_site_url();
			$webhook_url       = trailingslashit( $site_url ) . 'wp-json/leat/v1/webhooks';

			// First, clean up any old WordPress webhooks that are not in our required list
			foreach ( $existing_webhooks as $existing_webhook ) {
				if ( strpos( $existing_webhook->getName(), self::WEBHOOK_PREFIX ) === 0 ) {
					$event_type  = $existing_webhook->getEventType();
					$is_required = false;
					foreach ( self::REQUIRED_WEBHOOKS as $webhook_config ) {
						if ( $webhook_config['event_type'] === $event_type ) {
							$is_required = true;
							break;
						}
					}
					if ( ! $is_required ) {
						WebhookSubscription::delete( $existing_webhook->getUuid() );
					}
				}
			}

			// Then process required webhooks
			foreach ( self::REQUIRED_WEBHOOKS as $key => $webhook_config ) {
				try {
					$exists = false;
					foreach ( $existing_webhooks as $existing_webhook ) {
						if ( $existing_webhook->getEventType() === $webhook_config['event_type']
							&& strpos( $existing_webhook->getName(), self::WEBHOOK_PREFIX ) === 0 ) {
							$exists = true;
							// Update webhook if URL is wrong
							if ( $existing_webhook->getUrl() !== $webhook_url ) {
								WebhookSubscription::update(
									$existing_webhook->getUuid(),
									[
										'url'    => $webhook_url,
										'status' => 'ACTIVE',
									]
									);
							}
							break;
						}
					}

					if ( ! $exists ) {
						WebhookSubscription::create(
							[
								'name'       => $webhook_config['name'],
								'event_type' => $webhook_config['event_type'],
								'url'        => $webhook_url,
								'status'     => 'ACTIVE',
							]
							);
					}
				} catch ( \Exception $e ) {
					$this->logger->error( 'Failed to process webhook ' . $webhook_config['event_type'] . ': ' . $e->getMessage() );
					continue;
				}
			}

			return true;
		} catch ( \Throwable $th ) {
			$this->logger->error( 'Failed to list existing webhooks: ' . $th->getMessage() );
			return false;
		}
	}

	public function handle_webhook( $event_type, $data ) {
		switch ( $event_type ) {
			case 'voucher_created':
			case 'voucher_updated':
			case 'voucher_redeemed':
				$this->handle_voucher_webhook( $event_type, $data );
				break;
		}
	}

	private function handle_voucher_webhook( $event_type, $data ) {
		$voucher_sync = new VoucherSync( $this->connection );
		$contact_uuid = $data['contact_uuid'];
		$user         = $this->connection->get_user_from_leat_uuid( $contact_uuid );

		if ( ! $user ) {
			$this->logger->error( "No WordPress user found for contact UUID: $contact_uuid" );
			return;
		}

		// Fetch updated voucher data and update user meta
		try {
			$vouchers = \Piggy\Api\Models\Vouchers\Voucher::list(
				[
					'contact_uuid'   => $contact_uuid,
					'promotion_uuid' => $data['promotion_uuid'],
					'limit'          => 100,
					'page'           => 1,
				]
				);

			if ( ! empty( $vouchers ) ) {
				$voucher_sync->update_user_vouchers( $user->ID, $vouchers );
			}
		} catch ( \Throwable $th ) {
			$this->logger->error( 'Error processing voucher webhook: ' . $th->getMessage() );
		}
	}
}
