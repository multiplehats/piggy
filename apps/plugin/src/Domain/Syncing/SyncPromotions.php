<?php

namespace Leat\Domain\Syncing;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRulesService;

class SyncPromotions extends AbstractSync
{
	/**
	 * The promotion rules service.
	 *
	 * @var PromotionRulesService
	 */
	private PromotionRulesService $promotion_rules_service;

	/**
	 * The batch size.
	 *
	 * @var int
	 */
	protected const BATCH_SIZE = 50;

	/**
	 * Constructor.
	 */
	public function __construct(Connection $connection, PromotionRulesService $promotion_rules_service)
	{
		parent::__construct($connection);

		$this->promotion_rules_service = $promotion_rules_service;
	}

	/**
	 * Initialize the sync.
	 */
	public function init(): void
	{
		add_action('leat_run_promotions_sync', [$this, 'start_sync']);
	}

	/**
	 * Get the post type.
	 *
	 * @return string
	 */
	protected function get_post_type(): string
	{
		return 'leat_promotion_rule';
	}

	/**
	 * Get the action name.
	 *
	 * @return string
	 */
	protected function get_action_name(): string
	{
		return 'sync_promotions';
	}

	/**
	 * Get the UUID meta key.
	 *
	 * @return string
	 */
	protected function get_uuid_meta_key(): string
	{
		return '_leat_promotion_uuid';
	}

	/**
	 * Format the data.
	 *
	 * @param array $item
	 * @return array
	 */
	protected function format_data(array $item): array
	{
		return $item;
	}

	/**
	 * Upsert the item.
	 *
	 * @param array $data
	 * @param int|null $existing_id
	 */
	protected function upsert_item(array $data, ?int $existing_id = null): void
	{
		$this->promotion_rules_service->create_or_update($data, $existing_id);
	}
}
