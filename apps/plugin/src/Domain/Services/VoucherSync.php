<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRules as PromotionRuleService;
use Leat\Utils\Logger;
use Piggy\Api\Models\Vouchers\Voucher;

class VoucherSync {
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var PromotionRuleService
	 */
	private $promotion_rule_service;

	private const HOOK_SYNC_VOUCHERS = 'leat_sync_vouchers';
	private const HOOK_PROCESS_BATCH = 'leat_process_voucher_batch';
	private const BATCH_SIZE         = 10;

	public function __construct( Connection $connection ) {
		$this->connection             = $connection;
		$this->promotion_rule_service = new PromotionRuleService();
		$this->logger                 = new Logger();

		// Move hook registration to init hook
		add_action( 'init', [ $this, 'register_hooks' ] );
	}

	public function register_hooks() {
		// Only proceed if Action Scheduler is ready
		if ( ! class_exists( 'ActionScheduler_DataController' ) ) {
			return;
		}

		// Schedule daily sync if not already scheduled
		if ( ! as_next_scheduled_action( self::HOOK_SYNC_VOUCHERS ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, self::HOOK_SYNC_VOUCHERS );
		}

		// Register handlers for our actions
		add_action( self::HOOK_SYNC_VOUCHERS, [ $this, 'start_sync' ] );
		add_action( self::HOOK_PROCESS_BATCH, [ $this, 'process_batch' ], 10, 2 );
		add_action( 'leat_sync_promotions_complete', [ $this, 'handle_promotions_sync_complete' ] );

		// Add admin actions
		add_action( 'admin_post_cancel_voucher_sync', [ $this, 'handle_cancel_sync' ] );
	}

	public function handle_promotions_sync_complete() {
		// Only schedule if no sync is currently running
		if ( ! $this->is_sync_running() ) {
			as_enqueue_async_action( self::HOOK_SYNC_VOUCHERS );
		}
	}

	public function start_sync() {
		try {
			$this->logger->info( 'Starting voucher sync' );

			// Cancel any existing pending batch processes
			$this->cancel_pending_batches();

			$active_promotions = $this->promotion_rule_service->get_active_promotions();
			$this->logger->info( 'Found ' . count( $active_promotions ) . ' active promotions' );

			$users = get_users(
				[
					'meta_key' => 'leat_uuid',
					'fields'   => [ 'ID', 'user_email' ],
					'orderby'  => 'ID',
					'order'    => 'ASC',
				]
				);
			$this->logger->info( 'Found ' . count( $users ) . ' users with Leat UUID' );

			// Schedule batches for each promotion
			foreach ( $active_promotions as $promotion_uuid ) {
				$this->schedule_batches( $users, $promotion_uuid );
			}
		} catch ( \Throwable $th ) {
			$this->logger->error( 'Failed to start voucher sync: ' . $th->getMessage() );
			throw $th;
		}
	}

	private function schedule_batches( array $users, string $promotion_uuid ) {
		$batches = array_chunk( $users, self::BATCH_SIZE );

		foreach ( $batches as $index => $batch ) {
			as_enqueue_async_action(
				self::HOOK_PROCESS_BATCH,
				[
					'user_ids'       => array_column( $batch, 'ID' ),
					'promotion_uuid' => $promotion_uuid,
				],
				'leat-voucher-sync'
			);
		}
	}

	public function process_batch( array $user_ids, string $promotion_uuid ) {
		$this->connection->init_client();

		foreach ( $user_ids as $user_id ) {
			$contact_uuid = get_user_meta( $user_id, 'leat_uuid', true );
			if ( ! $contact_uuid ) {
				continue;
			}

			try {
				$vouchers = Voucher::list(
					[
						'contact_uuid'   => $contact_uuid,
						'promotion_uuid' => $promotion_uuid,
						'limit'          => 100,
						'page'           => 1,
					]
					);

				if ( ! empty( $vouchers ) ) {
					$this->logger->info( 'Found ' . count( $vouchers ) . " vouchers for user {$user_id} and promotion {$promotion_uuid}" );

					$this->update_user_vouchers( $user_id, $vouchers );
				} else {
					$this->logger->info( "No vouchers found for user {$user_id} and promotion {$promotion_uuid}" );
				}
			} catch ( \Throwable $th ) {
				$this->logger->error( "Error processing vouchers for user {$user_id}: " . $th->getMessage() );
				// Let Action Scheduler handle the retry
				throw $th;
			}
		}
	}

	public function handle_cancel_sync() {
		check_admin_referer( 'cancel_voucher_sync' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$this->cancel_pending_batches();

		wp_redirect( admin_url( 'admin.php?page=your-plugin&sync=cancelled' ) );
		exit;
	}

	private function cancel_pending_batches() {
		as_unschedule_all_actions( self::HOOK_PROCESS_BATCH, [], 'leat-voucher-sync' );
		$this->logger->info( 'Cancelled all pending voucher sync batches' );
	}

	private function is_sync_running(): bool {
		return as_has_scheduled_action( self::HOOK_PROCESS_BATCH, [], 'leat-voucher-sync' );
	}

	private function voucherToArray( Voucher $voucher ): array {
		return [
			'uuid'            => $voucher->getUuid(),
			'code'            => $voucher->getCode(),
			'status'          => $voucher->getStatus(),
			'name'            => $voucher->getName(),
			'description'     => $voucher->getDescription(),
			'expiration_date' => $voucher->getExpirationDate(),
			'activation_date' => $voucher->getActivationDate(),
			'redeemed_at'     => $voucher->getRedeemedAt(),
			'is_redeemed'     => $voucher->isRedeemed(),
			'promotion'       => [
				'uuid' => $voucher->getPromotion()?->getUuid(),
				'name' => $voucher->getPromotion()?->getName(),
			],
		];
	}

	public function update_user_vouchers( $user_id, $vouchers ) {
		$existing_vouchers = get_user_meta( $user_id, 'leat_vouchers', true ) ?: [];
		$this->logger->info( "Updating vouchers for user {$user_id}. Current count: " . count( $existing_vouchers ) . ', New count: ' . count( $vouchers ) );

		$updated_vouchers = [];

		foreach ( $vouchers as $voucher ) {
			// Convert Voucher object to array using our helper method
			$voucher_data = $this->voucherToArray( $voucher );

			$updated_vouchers[ $voucher_data['uuid'] ] = [
				'code'            => $voucher_data['code'],
				'status'          => $voucher_data['status'],
				'name'            => $voucher_data['name'],
				'description'     => $voucher_data['description'],
				'expiration_date' => $voucher_data['expiration_date'],
				'activation_date' => $voucher_data['activation_date'],
				'redeemed_at'     => $voucher_data['redeemed_at'],
				'is_redeemed'     => $voucher_data['is_redeemed'],
				'promotion_uuid'  => $voucher_data['promotion']['uuid'],
				'promotion_name'  => $voucher_data['promotion']['name'],
			];
		}

		update_user_meta( $user_id, 'leat_vouchers', $updated_vouchers );
	}

	public function get_vouchers_for_user( $user_id ) {
		return get_user_meta( $user_id, 'leat_vouchers', true ) ?: [];
	}
}
