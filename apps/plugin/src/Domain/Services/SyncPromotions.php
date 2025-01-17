<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRules as PromotionRuleService;
use Leat\Utils\Logger;

class SyncPromotions {
	/**
	 * The Connection instance.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * The Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var PromotionRuleService
	 */
	private $promotion_rules;

	/**
	 * The hook for syncing promotions.
	 *
	 * @var string
	 */
	private const HOOK_SYNC_PROMOTIONS = 'leat_sync_promotions';

	/**
	 * Constructor.
	 *
	 * @param Connection           $connection The Connection instance.
	 * @param PromotionRuleService $promotion_rules The PromotionRules service.
	 */
	public function __construct( Connection $connection, PromotionRuleService $promotion_rules ) {
		$this->connection      = $connection;
		$this->promotion_rules = $promotion_rules;
		$this->logger          = new Logger();
	}

	/**
	 * Initialize the sync service.
	 */
	public function init() {
		add_action( 'init', [ $this, 'setup_scheduler' ], 20 );
		add_action( self::HOOK_SYNC_PROMOTIONS, [ $this, 'sync_promotions' ] );
	}

	/**
	 * Setup the scheduler after Action Scheduler is ready.
	 */
	public function setup_scheduler() {
		if ( ! class_exists( 'ActionScheduler_DataController' ) ) {
			return;
		}

		if ( ! as_next_scheduled_action( self::HOOK_SYNC_PROMOTIONS ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, self::HOOK_SYNC_PROMOTIONS );
		}
	}

	/**
	 * Handle manual sync request from admin.
	 */
	public function manual_sync() {
		$this->start_sync();

		return $this->get_task_information();
	}

	/**
	 * Get information about scheduled promotion sync tasks.
	 *
	 * @return array Task information including next scheduled time, pending actions, and running status.
	 */
	public function get_task_information() {
		return [
			'next_scheduled'  => as_next_scheduled_action( self::HOOK_SYNC_PROMOTIONS ),
			'is_running'      => $this->is_sync_running(),
			'pending_count'   => count(
				as_get_scheduled_actions(
				[
					'hook'   => self::HOOK_SYNC_PROMOTIONS,
					'status' => \ActionScheduler_Store::STATUS_PENDING,
				],
				'ids'
				)
				),
			'running_count'   => count(
				as_get_scheduled_actions(
				[
					'hook'   => self::HOOK_SYNC_PROMOTIONS,
					'status' => \ActionScheduler_Store::STATUS_RUNNING,
				],
				'ids'
				)
				),
			'failed_count'    => count(
				as_get_scheduled_actions(
				[
					'hook'   => self::HOOK_SYNC_PROMOTIONS,
					'status' => \ActionScheduler_Store::STATUS_FAILED,
				],
				'ids'
				)
				),
			'completed_count' => count(
				as_get_scheduled_actions(
				[
					'hook'   => self::HOOK_SYNC_PROMOTIONS,
					'status' => \ActionScheduler_Store::STATUS_COMPLETE,
				],
				'ids'
				)
				),
		];
	}

	/**
	 * Start a promotion sync.
	 */
	public function start_sync() {
		if ( ! $this->is_sync_running() ) {
			as_enqueue_async_action( self::HOOK_SYNC_PROMOTIONS );
		}
	}

	/**
	 * Main sync process.
	 *
	 * @return bool True if the sync is successful, false otherwise.
	 * @throws \Throwable If the sync fails.
	 */
	public function sync_promotions() {
		$client = $this->connection->init_client();
		if ( ! $client ) {
			$this->logger->error( 'Failed to initialize client for promotion sync' );
			return false;
		}

		try {
			$this->logger->info( 'Starting promotion sync' );

			$promotions = $this->connection->get_promotions();
			if ( ! $promotions ) {
				$this->logger->info( 'No promotions found to sync' );
				return true;
			}

			$this->logger->info( 'Retrieved ' . count( $promotions ) . ' promotions' );

			$prepared_args = array(
				'post_type'      => 'leat_promotion_rule',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
			);

			$current_promotion_rules = get_posts( $prepared_args );
			$existing_uuids          = array_column( $current_promotion_rules, '_leat_promotion_uuid', 'ID' );
			$processed_uuids         = [];
			$updated_count           = 0;
			$created_count           = 0;

			foreach ( $promotions as $promotion ) {
				$mapped_promotion = $this->map_promotion( $promotion );
				$existing_post_id = array_search( $promotion['uuid'], $existing_uuids, true );

				if ( false !== $existing_post_id ) {
					$this->logger->info( 'Updating existing promotion rule: ' . $existing_post_id );
					$this->promotion_rules->create_or_update_promotion_rule_from_promotion( $mapped_promotion, $existing_post_id );
					$updated_count++;
				} else {
					$this->logger->info( 'Creating new promotion rule for UUID: ' . $promotion['uuid'] );
					$this->promotion_rules->create_or_update_promotion_rule_from_promotion( $mapped_promotion );
					$created_count++;
				}

				$processed_uuids[] = $promotion['uuid'];
			}

			$this->handle_deletions( $existing_uuids, $processed_uuids );
			$this->handle_duplicates( $processed_uuids );

			$this->logger->info( "Promotion sync completed. Updated: $updated_count, Created: $created_count" );

			do_action( 'leat_sync_promotions_complete', $processed_uuids );

			return true;

		} catch ( \Throwable $th ) {
			$this->logger->error( 'Failed to sync promotions: ' . $th->getMessage() );
			throw $th;
		}
	}

	/**
	 * Map promotion data to the required format.
	 */
	private function map_promotion( $promotion ) {
		$mapped = [
			'title'              => $promotion['title'],
			'uuid'               => $promotion['uuid'],
			'voucherLimit'       => $promotion['voucherLimit'],
			'limitPerContact'    => $promotion['limitPerContact'],
			'expirationDuration' => $promotion['expirationDuration'],
		];

		if ( isset( $promotion['media'] ) ) {
			$mapped['image'] = $promotion['media']['value'];
		}

		return $mapped;
	}

	/**
	 * Handle deletion of promotions that no longer exist.
	 */
	private function handle_deletions( $existing_uuids, $processed_uuids ) {
		$uuids_to_delete = array_diff( $existing_uuids, $processed_uuids );
		$delete_count    = count( $uuids_to_delete );

		if ( $delete_count > 0 ) {
			$this->logger->info( "Deleting $delete_count promotion rules that no longer exist" );
			$this->promotion_rules->delete_promotion_rules_by_uuids( $uuids_to_delete );
		}
	}

	/**
	 * Handle duplicate promotions.
	 */
	private function handle_duplicates( $processed_uuids ) {
		$this->logger->info( 'Checking for duplicate promotion rules' );
		$this->promotion_rules->handle_duplicated_promotion_rules( $processed_uuids );
	}

	/**
	 * Check if a sync is currently running.
	 */
	private function is_sync_running(): bool {
		return as_has_scheduled_action( self::HOOK_SYNC_PROMOTIONS );
	}
}

