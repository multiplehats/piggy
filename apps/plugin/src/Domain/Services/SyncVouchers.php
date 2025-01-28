<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRules as PromotionRuleService;
use Leat\Domain\Syncing\BackgroundProcess;
use Leat\Utils\Coupons;
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
		add_action('leat_webhook_voucher_updated', [$this, 'handle_voucher_updated_webhook']);
		add_action('leat_webhook_voucher_created', [$this, 'handle_voucher_created_webhook']);
		add_action('leat_webhook_voucher_deleted', [$this, 'handle_voucher_deleted_webhook']);
		add_action('leat_webhook_voucher_redeemed', [$this, 'handle_voucher_redeemed_webhook']);
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
					$formatted_promotion_rule['leatPromotionUuid']['value'],
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

			return false;
		}

		return false;
	}

	/**
	 * Format voucher data from webhook payload into standardized structure.
	 *
	 * @param array $voucher_data Raw voucher data from webhook
	 * @return array Formatted voucher data
	 */
	private function format_voucher_webhook($voucher_data)
	{
		return array(
			'code' => $voucher_data['code'],
			'uuid' => $voucher_data['uuid'],
			'name' => $voucher_data['name'],
			'status' => $voucher_data['status'],
			'description' => $voucher_data['description'],
			'contact_uuid' => isset($voucher_data['contact']) ? $voucher_data['contact']['uuid'] : null,
			'expiration_date' => isset($voucher_data['expiration_date']) ? new \DateTime($voucher_data['expiration_date']) : null,
			'activation_date' => isset($voucher_data['activation_date']) ? new \DateTime($voucher_data['activation_date']) : null,
			'redeemed_at' => isset($voucher_data['redeemed_at']) ? new \DateTime($voucher_data['redeemed_at']) : null,
			'is_redeemed' => $voucher_data['is_redeemed'],
			'promotion' => $voucher_data['promotion'],
		);
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
			$voucher_data = $this->connection->format_voucher($voucher);

			$this->upsert_coupon_for_promotion_rule($formatted_promotion_rule, $voucher_data);
		}
	}

	public function handle_voucher_deleted_webhook($voucher)
	{
		try {
			$coupon = Coupons::find_coupon_by_code($voucher['code']);

			$coupon->delete();

			$this->logger->info('Deleted coupon for voucher ' . $voucher['code']);
		} catch (\Throwable $th) {
			$this->logger->error('Failed to delete coupon for voucher ' . $voucher['code'], [
				'error' => $th->getMessage(),
				'voucher' => $voucher,
			]);
		}
	}

	public function handle_voucher_created_webhook($voucher)
	{
		// We need more info on whether we'll impelement this.
		// What if someone creates 10k coupons?
	}

	public function handle_voucher_updated_webhook($data)
	{
		try {
			// Extract voucher data from webhook payload
			$voucher_data = isset($data['voucher']) ? $data['voucher'] : $data;

			$coupon = Coupons::find_or_create_coupon_by_code($voucher_data['code']);

			$promotion_rule = $coupon->get_meta('_leat_promotion_uuid');

			if (! $promotion_rule) {
				$this->logger->error('Promotion rule not found for voucher ' . $voucher_data['code']);
				return;
			}

			$formatted_promotion_rule = $this->promotion_rules->get_promotion_rule_by_leat_uuid($promotion_rule);
			$voucher_data = $this->format_voucher_webhook($voucher_data);

			$this->upsert_coupon_for_promotion_rule($formatted_promotion_rule, $voucher_data);
		} catch (\Exception $e) {
			// Coupon doesn't exist, create new one.
			// $coupon = new \WC_Coupon();
			// $coupon->set_code(strtoupper($voucher_data['code']));
		}
	}

	/**
	 * When a voucher gets redeemed, we need to check if we have to disable the coupon.
	 * Because it coudl be used on this WooCommerce store or in a physical store (or other location).
	 *
	 * @param array $voucher The voucher data.
	 */
	public function handle_voucher_redeemed_webhook($voucher)
	{
		$this->logger->debug('Voucher redeemed webhook processed', [
			'voucher' => $voucher
		]);

		try {
			$coupon = Coupons::find_coupon_by_code($voucher['code']);
			$coupon_status = $coupon->get_status();

			if ($coupon_status === 'publish') {
				$coupon->set_status('draft');

				// Get contact UUID from coupon metadata
				$contact_uuid = $coupon->get_meta('_leat_contact_uuid');

				if ($contact_uuid) {
					$user = $this->connection->find_or_create_wp_user_by_uuid($contact_uuid);
					if ($user) {
						$coupon->set_used_by([$user->user_email]);
					}
				}

				$coupon->save();

				$this->logger->info('Coupon disabled for voucher ' . $voucher['code']);
			}
		} catch (\Throwable $th) {
			$this->logger->error('Failed to disable coupon for voucher ' . $voucher['code'], [
				'error' => $th->getMessage(),
				'voucher' => $voucher,
			]);
		}
	}

	public function upsert_coupon_for_promotion_rule($formatted_rule, $voucher_data)
	{
		$coupon = Coupons::find_or_create_coupon_by_code($voucher_data['code']);
		$contact_uuid = $voucher_data['contact_uuid'];
		$is_redeemed  = $voucher_data['is_redeemed'];

		try {
			/**
			 * Set a description so that it's easier to identify how the coupon was created.
			 */
			$coupon->set_description(sprintf(
				/* translators: %s: The promotion rule name */
				__('Leat Promotion Voucher: %s', 'leat-crm'),
				$voucher_data['name']
			));

			$coupon->update_meta_data('_leat_contact_uuid', $contact_uuid);
			$coupon->update_meta_data('_leat_voucher_uuid', $voucher_data['uuid']);
			$coupon->update_meta_data('_leat_promotion_uuid', $voucher_data['promotion']['uuid']);
			$coupon->update_meta_data('_leat_promotion_rule_id', $formatted_rule['id']);

			/**
			 * Set the coupon individual use.
			 */
			if ($formatted_rule['individualUse']['value'] === 'on') {
				$coupon->set_individual_use(true);
			} else {
				$coupon->set_individual_use(false);
			}

			// Each voucher can only be used once.
			$coupon->set_usage_limit(1);
			$coupon->set_usage_limit_per_user(1);

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

			/**
			 * Assign a minimum purchase amount to the coupon.
			 */
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
				$contact      = $this->connection->get_contact_by_uuid($contact_uuid);

				/**
				 * Silently create a new user, without sending notification emails.
				 *
				 * @var \WP_User|WP_Error
				 */
				$user = $this->connection->find_or_create_wp_user_by_uuid($contact_uuid);


				if (!$user) {
					$this->logger->error('Failed to create user for voucher ' . $voucher_data['code']);

					return;
				}

				$this->logger->info('Created user for voucher ' . $voucher_data['code'], [
					'user' => $user,
				]);

				$coupon->set_email_restrictions([$user->user_email]);

				// If we have a wp user and the voucher is redeemed, set the coupon status to trash.
				if ($is_redeemed) {
					$email = $user->user_email;

					$coupon->set_used_by([$email]);
				}
			} else {
				$coupon->set_email_restrictions([]);
			}

			/**
			 * Set the coupon status.
			 */
			if ($voucher_data['status'] === 'INACTIVE') {
				$coupon->set_status('draft');
				$this->logger->info('Voucher is inactive, setting coupon status to draft for ' . $voucher_data['code']);
			} else if ($voucher_data['status'] === 'DEACTIVATED') {
				$coupon->set_status('draft');
				$this->logger->info('Voucher is deactivated, setting coupon status to draft for ' . $voucher_data['code']);
			} else {
				$coupon->set_status('publish');
				$this->logger->info('Voucher is active, setting coupon status to publish for ' . $voucher_data['code']);
			}

			$coupon->save();
		} catch (\Throwable $th) {
			$this->logger->error('Failed to save coupon for voucher ' . $voucher_data['code'], [
				'error' => $th->getMessage(),
				'voucher_data' => $voucher_data,
				'formatted_rule' => $formatted_rule,
			]);

			return;
		}
	}
}
