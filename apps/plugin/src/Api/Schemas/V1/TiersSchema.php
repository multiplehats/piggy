<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Contact schema class.
 *
 * @internal
 */
class TiersSchema extends AbstractSchema
{
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'tiers';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'tiers';

	/**
	 * Coupons schema properties.
	 *
	 * @return array
	 */
	public function get_properties()
	{
		return [
			'id'          => [
				'type' => 'string',
			],
			'name'         => [
				'type' => 'string',
			],
			'position' => [
				'type' => 'integer',
			],
			'media'    => [
				'type' => 'object',
			],
		];
	}
}
