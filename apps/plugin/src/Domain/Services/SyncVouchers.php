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
	private const BATCH_SIZE = 500;

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
		// add_action('save_post_leat_promotion_rule', [$this, 'start_sync']);
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
			$this->logger->warning("Sync process is already running for {$this->action}. Skipping new sync request.");
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

				$this->logger->info(
					sprintf(
						'Synced %d vouchers for promotion %s',
						count($item['vouchers']),
						$item['promotion_rule']['leatPromotionUuid']['value']
					)
				);

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
					$item['promotion_rule']['leatPromotionUuid']['value'],
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
			error_log('voucher: ' . $voucher->getUuid());
			error_log('expiration_date: ' . $voucher->getExpirationDate() instanceof \DateTime ? $voucher->getExpirationDate()->format('Y-m-d H:i:s') : 'null');
			error_log('activation_date: ' . $voucher->getActivationDate() instanceof \DateTime ? $voucher->getActivationDate()->format('Y-m-d H:i:s') : 'null');
			$this->upsert_coupon_for_promotion_rule($formatted_promotion_rule, $voucher);
		}
	}

	public function upsert_coupon_for_promotion_rule($formatted_rule, Voucher $voucher,)
	{
		$voucher_data = $this->connection->format_voucher($voucher);

		if (isset($voucher_data['expiration_date']) && $voucher_data['expiration_date'] instanceof \DateTime) {
			error_log('expiration_date: ' . $voucher_data['expiration_date']->format('Y-m-d H:i:s'));
		}

		try {
			// Try to load existing coupon.
			$coupon = new \WC_Coupon($voucher_data['code']);
		} catch (\Exception $e) {
			// Coupon doesn't exist, create new one.
			$coupon = new \WC_Coupon();

			$coupon->set_code(strtoupper($voucher_data['code']));
		}

		// WE set a descripotion for internal use.
		$descripotion = sprintf(
			/* translators: %s: The promotion rule name */
			__('Leat Promotion Voucher: %s', 'leat-crm'),
			$voucher_data['name']
		);
		$coupon->set_description($descripotion);

		$contact_uuid = $voucher_data['contact_uuid'];
		$wp_user      = $this->connection->get_user_from_leat_uuid($contact_uuid);
		$is_redeemed  = $voucher_data['is_redeemed'];

		// If we have a wp user and the voucher is redeemed, set the coupon status to trash.
		if ($wp_user && $is_redeemed) {
			$coupon->set_used_by([$wp_user->user_email]);
		}

		if ($formatted_rule['individualUse']['value'] === 'on') {
			$coupon->set_individual_use(true);
		} else {
			$coupon->set_individual_use(false);
		}

		// Each voucher can only be used once.
		$coupon->set_usage_limit(1);

		/**
		 * Expiration date of the voucher.
		 *
		 * @var \DateTime|null
		 */
		$expiration_date = $voucher_data['expiration_date'] instanceof \DateTime ? $voucher_data['expiration_date'] : null;
		if ($expiration_date) {
			// UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
			$coupon->set_date_expires($expiration_date->getTimestamp());
		}

		$discount_type = $this->promotion_rules->get_discount_type($formatted_rule['discountType']['value']);
		if ($discount_type) {
			$coupon->set_discount_type($discount_type);
		}

		$discount_value = $formatted_rule['discountValue']['value'];
		if ($discount_value) {
			$coupon->set_amount($discount_value);
		}

		if ($voucher_data['expiration_date']) {
			$coupon->set_date_expires(strtotime($voucher_data['expiration_date']));
		}

		$coupon->update_meta_data('_leat_voucher_uuid', $voucher_data['uuid']);
		$coupon->update_meta_data('_leat_promotion_uuid', $voucher_data['promotion']['uuid']);

		// error_log('minimumPurchaseAmount: ' . $formatted_rule['minimumPurchaseAmount']['value']);

		// Check for minimum purchase amount.
		if (
			isset($formatted_rule['minimumPurchaseAmount']) &&
			is_numeric($formatted_rule['minimumPurchaseAmount']['value'])
		) {
			$min_amount = floatval($formatted_rule['minimumPurchaseAmount']['value']);
			if ($min_amount > 0) {
				$coupon->set_minimum_amount($min_amount);
			}
		}

		if (isset($voucher_data['custom_attributes'])) {
			foreach ($voucher_data['custom_attributes'] as $key => $value) {
				$coupon->update_meta_data("_leat_custom_attribute_{$key}", $value);
			}
		}

		// Check if any products are selected.
		if (isset($formatted_rule['selectedProducts']['value'])) {
			$coupon->set_product_ids($formatted_rule['selectedProducts']['value']);
		}

		// Handle contact-specific restrictions.
		if (isset($voucher_data['contact_uuid'])) {
			$coupon->update_meta_data('_leat_contact_uuid', $contact_uuid);

			if ($wp_user) {
				$coupon->set_email_restrictions([$wp_user->user_email]);
			}
		}

		$coupon->save();
	}
}
