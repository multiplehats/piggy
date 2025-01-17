<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRules as PromotionRuleService;
use Leat\Domain\Syncing\BackgroundProcess;
use Leat\Utils\Logger;

class SyncPromotions extends BackgroundProcess
{
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
	 * The prefix for the background process.
	 *
	 * @var string
	 */
	protected $prefix = 'leat';

	/**
	 * The action name.
	 *
	 * @var string
	 */
	protected $action = 'sync_promotions';

	/**
	 * The batch size.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 20;

	/**
	 * Constructor.
	 *
	 * @param Connection           $connection The Connection instance.
	 * @param PromotionRuleService $promotion_rules The PromotionRules service.
	 */
	public function __construct(Connection $connection, PromotionRuleService $promotion_rules)
	{
		parent::__construct();
		$this->connection      = $connection;
		$this->promotion_rules = $promotion_rules;
		$this->logger          = new Logger();
	}


	public function init()
	{
		add_action('leat_run_promotions_sync', [$this, 'start_sync']);
	}

	/**
	 * Start the sync process.
	 *
	 * @return bool
	 *
	 * @throws \Throwable If the sync process fails.
	 */
	public function start_sync()
	{
		// Check if a sync process is already running
		if ($this->is_active()) {
			$this->logger->warning('Sync process is already running. Skipping new sync request.');
			return false;
		}

		try {
			$this->logger->info('Starting promotion sync');

			$client = $this->connection->init_client();
			if (! $client) {
				$this->logger->error('Failed to initialize client for promotion sync');
				return false;
			}

			$promotions = $this->connection->get_promotions();
			if (! $promotions) {
				$this->logger->info('No promotions found to sync');
				return true;
			}

			$this->logger->info('Retrieved ' . count($promotions) . ' promotions');

			$this->update_stats([
				'total_items' => 0,
				'remaining_items' => count($promotions)
			]);

			$batches = array_chunk($promotions, self::BATCH_SIZE);

			foreach ($batches as $batch) {
				$this->push_to_queue($batch);
			}

			$this->save()->dispatch();

			return true;
		} catch (\Throwable $th) {
			$this->logger->error('Failed to start promotion sync: ' . $th->getMessage());
			throw $th;
		}
	}

	/**
	 * Task
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task($item)
	{
		try {
			$processed_uuids = [];
			$updated_count = 0;
			$created_count = 0;

			foreach ($item as $promotion) {
				$mapped_promotion = $this->map_promotion($promotion);
				$existing_post_id = $this->get_existing_promotion_id($promotion['uuid']);

				if ($existing_post_id) {
					$this->promotion_rules->create_or_update_promotion_rule_from_promotion($mapped_promotion, $existing_post_id);
					$updated_count++;
				} else {
					$this->promotion_rules->create_or_update_promotion_rule_from_promotion($mapped_promotion);
					$created_count++;
				}

				$processed_uuids[] = $promotion['uuid'];
			}

			// Update processed count
			$this->update_stats([
				'items_processed' => $this->stats['items_processed'] + count($item),
				'remaining_items' => $this->stats['total_items'] - ($this->stats['items_processed'] + count($item))
			]);

			$this->logger->info("Processed batch. Updated: $updated_count, Created: $created_count");

			// Store processed UUIDs for later cleanup
			update_option('leat_processed_promotion_uuids', array_merge(
				get_option('leat_processed_promotion_uuids', []),
				$processed_uuids
			));
		} catch (\Throwable $th) {
			$this->logger->error('Error processing promotion batch: ' . $th->getMessage());
		}

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete()
	{
		parent::complete();

		$processed_uuids = get_option('leat_processed_promotion_uuids', []);

		// Clean up old promotions
		$this->handle_deletions_and_duplicates($processed_uuids);

		// Clean up temporary data
		delete_option('leat_processed_promotion_uuids');

		do_action('leat_sync_promotions_complete', $processed_uuids);

		$this->logger->info('Promotion sync completed');
	}

	/**
	 * Get existing promotion ID by UUID.
	 *
	 * @param string $uuid
	 * @return int|false
	 */
	private function get_existing_promotion_id($uuid)
	{
		$posts = get_posts([
			'post_type' => 'leat_promotion_rule',
			'posts_per_page' => 1,
			'meta_key' => '_leat_promotion_uuid',
			'meta_value' => $uuid,
			'post_status' => ['publish', 'draft', 'pending'],
		]);

		return !empty($posts) ? $posts[0]->ID : false;
	}

	/**
	 * Handle deletions and duplicates cleanup.
	 *
	 * @param array $processed_uuids
	 */
	private function handle_deletions_and_duplicates($processed_uuids)
	{
		$existing_posts = get_posts([
			'post_type' => 'leat_promotion_rule',
			'posts_per_page' => -1,
			'post_status' => ['publish', 'draft', 'pending'],
		]);

		$existing_uuids = array_column($existing_posts, '_leat_promotion_uuid', 'ID');

		$this->handle_deletions($existing_uuids, $processed_uuids);
		$this->handle_duplicates($processed_uuids);
	}

	/**
	 * Map promotion data to the required format.
	 */
	private function map_promotion($promotion)
	{
		$mapped = [
			'title'              => $promotion['title'],
			'uuid'               => $promotion['uuid'],
			'voucherLimit'       => $promotion['voucherLimit'],
			'limitPerContact'    => $promotion['limitPerContact'],
			'expirationDuration' => $promotion['expirationDuration'],
		];

		if (isset($promotion['media'])) {
			$mapped['image'] = $promotion['media']['value'];
		}

		return $mapped;
	}

	/**
	 * Handle deletion of promotions that no longer exist.
	 */
	private function handle_deletions($existing_uuids, $processed_uuids)
	{
		$uuids_to_delete = array_diff($existing_uuids, $processed_uuids);
		$delete_count    = count($uuids_to_delete);

		if ($delete_count > 0) {
			$this->logger->info("Deleting $delete_count promotion rules that no longer exist");
			$this->promotion_rules->delete_promotion_rules_by_uuids($uuids_to_delete);
		}
	}

	/**
	 * Handle duplicate promotions.
	 */
	private function handle_duplicates($processed_uuids)
	{
		$this->logger->info('Checking for duplicate promotion rules');
		$this->promotion_rules->handle_duplicated_promotion_rules($processed_uuids);
	}
}
