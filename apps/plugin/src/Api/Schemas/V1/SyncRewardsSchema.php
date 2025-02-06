<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Settings class.
 *
 * @internal
 */
class SyncRewardsSchema extends AbstractSchema
{
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'sync-rewards';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'sync-rewards';

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties()
	{
		return [
			'success' => [
				'type' => 'boolean',
				'description' => 'Whether the sync request was successful',
				'readonly' => true,
			],
			'status' => [
				'type' => 'object',
				'description' => 'Sync process status information',
				'readonly' => true,
				'properties' => [
					'is_running' => [
						'type' => 'boolean',
						'description' => 'Whether a sync process is currently running',
					],
					'last_sync' => [
						'type' => 'string',
						'format' => 'date-time',
						'description' => 'Timestamp of the last successful sync',
					],
				],
			],
		];
	}
}
