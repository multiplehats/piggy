<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRules as PromotionRuleService;
use Leat\Domain\Syncing\BackgroundProcess;
use Leat\Utils\Common;
use Leat\Utils\Logger;
use Piggy\Api\Models\Vouchers\Voucher;

class SyncVouchers extends BackgroundProcess
{
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
	protected $action = 'sync_vouchers';

	/**
	 * The batch size.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 2;

	/**
	 * @var PromotionRuleService
	 */
	private $promotion_rules;

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
	 * Constructor.
	 *
	 * @param Connection $connection The Connection instance.
	 */
	public function __construct(Connection $connection, PromotionRules $promotion_rules)
	{
		parent::__construct();
		$this->promotion_rules = $promotion_rules;
		$this->connection      = $connection;
		$this->logger          = new Logger();
	}

	public function init()
	{
		add_action('leat_run_vouchers_sync', [$this, 'start_sync']);
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
			$this->logger->info('Starting voucher sync');

			$active_promotions = $this->promotion_rules->get_active_promotion_rules();
			$this->logger->info('Found ' . count($active_promotions) . ' active promotions');


			foreach ($active_promotions as $promotion) {
				$this->queue_vouchers_for_promotion($promotion);
			}

			$this->save()->dispatch();

			return true;
		} catch (\Throwable $th) {
			$this->logger->error('Failed to start voucher sync: ' . $th->getMessage());
			throw $th;
		}
	}

	/**
	 * Queue vouchers for a promotion.
	 *
	 * @param array $formatted_promotion_rule The formatted promotion rule.
	 *
	 * @throws \Throwable If the vouchers cannot be queued.
	 */
	private function queue_vouchers_for_promotion(array $formatted_promotion_rule)
	{
		try {
			$this->connection->init_client();
			$page = 1;
			$voucher_count = 0;

			do {
				$uuid = $formatted_promotion_rule['leatPromotionUuid']['value'];
				$vouchers = Voucher::list(
					[
						'promotion_uuid' => $uuid,
						'limit'          => self::BATCH_SIZE,
						'page'           => $page,
					]
				);

				if (empty($vouchers)) {
					break;
				}

				$voucher_count += count($vouchers);

				$this->update_stats([
					'total_items' => $this->stats['total_items'] + count($vouchers),
					'remaining_items' => $this->stats['total_items'] + count($vouchers) - $this->stats['items_processed']
				]);

				$this->logger->info(
					sprintf(
						'Queueing batch for promotion %s (page %d, vouchers: %d, status: %s)',
						$uuid,
						$page,
						count($vouchers),
						$this->get_status()
					)
				);

				$this->push_to_queue(
					[
						'promotion_rule' => $formatted_promotion_rule,
						'vouchers'       => $vouchers,
					]
				);

				$page++;
			} while (! empty($vouchers));
		} catch (\Throwable $th) {
			$this->logger->error(
				sprintf(
					'Failed to queue vouchers for promotion %s: %s',
					$formatted_promotion_rule['leatPromotionUuid'],
					$th->getMessage()
				)
			);
			throw $th;
		}
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task($item)
	{
		try {
			if (! empty($item['vouchers'])) {
				$this->sync_vouchers_to_woocommerce($item['promotion_rule'], $item['vouchers']);

				// Update processed count
				$this->update_stats([
					'items_processed' => $this->stats['items_processed'] + count($item['vouchers']),
					'remaining_items' => $this->stats['total_items'] - ($this->stats['items_processed'] + count($item['vouchers']))
				]);
			}
		} catch (\Throwable $th) {
			$this->logger->error(
				sprintf(
					'Error processing vouchers for promotion %s: %s',
					$item['promotion_rule']['leatPromotionUuid'],
					$th->getMessage()
				)
			);
		}

		return false;
	}

	/**
	 * Syncs vouchers to WooCommerce.
	 *
	 * @param array          $formatted_promotion_rule The formatted promotion rule.
	 * @param array<Voucher> $vouchers The vouchers to sync.
	 */
	private function sync_vouchers_to_woocommerce($formatted_promotion_rule, array $vouchers)
	{
		foreach ($vouchers as $voucher) {
			$this->promotion_rules->upsert_coupon_for_promotion_rule($formatted_promotion_rule, $voucher);
		}
	}
}
