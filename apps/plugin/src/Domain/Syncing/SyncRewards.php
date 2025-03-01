<?php

namespace Leat\Domain\Syncing;

use Leat\Api\Connection;
use Leat\Domain\Services\SpendRulesService;

/**
 * Syncs Leat Rewards with the Spend Rules custom post type.
 */
class SyncRewards extends AbstractSync
{
	/**
	 * The spend rules service.
	 *
	 * @var SpendRulesService
	 */
	private SpendRulesService $spend_rules_service;

	protected const BATCH_SIZE = 50;

	public function __construct(Connection $connection, SpendRulesService $spend_rules_service)
	{
		parent::__construct($connection);
		$this->spend_rules_service = $spend_rules_service;
	}

	public function init(): void
	{
		add_action('leat_run_spend_rules_sync', [$this, 'start_sync']);
	}

	protected function get_post_type(): string
	{
		return 'leat_spend_rule';
	}

	protected function get_action_name(): string
	{
		return 'sync_spend_rules';
	}

	protected function get_uuid_meta_key(): string
	{
		return '_leat_reward_uuid';
	}

	protected function format_data(array $item): array
	{
		return $item;
	}

	protected function upsert_item(array $data, ?int $existing_id = null): void
	{
		$this->spend_rules_service->create_or_update_spend_rule_from_reward($data, $existing_id);
	}
}
