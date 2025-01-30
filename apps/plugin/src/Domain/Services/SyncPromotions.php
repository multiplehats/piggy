<?php

namespace Leat\Domain\Services;

use Leat\Api\Connection;
use Leat\Domain\Services\PromotionRules;

class SyncPromotions extends AbstractSync
{
	/**
	 * The promotion rules service.
	 *
	 * @var PromotionRules
	 */
	private PromotionRules $promotion_rules;

	// Increase batch size for promotions since they're simple
	protected const BATCH_SIZE = 50;

	public function __construct(Connection $connection, PromotionRules $promotion_rules)
	{
		parent::__construct($connection);
		$this->promotion_rules = $promotion_rules;
	}

	public function init(): void
	{
		add_action('leat_run_promotions_sync', [$this, 'start_sync']);
	}

	protected function get_post_type(): string
	{
		return 'leat_promotion_rule';
	}

	protected function get_action_name(): string
	{
		return 'sync_promotions';
	}

	protected function get_uuid_meta_key(): string
	{
		return '_leat_promotion_uuid';
	}

	protected function format_data(array $item): array
	{
		return $item;
	}

	protected function upsert_item(array $data, ?int $existing_id = null): void
	{
		$this->promotion_rules->create_or_update_promotion_rule_from_promotion($data, $existing_id);
	}
}
