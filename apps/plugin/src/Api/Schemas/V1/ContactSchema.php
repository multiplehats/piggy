<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Contact schema class.
 *
 * @internal
 */
class ContactSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'contact';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'contact';

	/**
	 * Coupons schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'uuid'          => [
				'type' => 'string',
			],
			'email'         => [
				'type' => 'string',
			],
			'subscriptions' => [
				'type' => 'array',
			],
			'attributes'    => [
				'type' => 'object',
			],
			'balance'       => [
				'type' => 'object',
			],
		];
	}
}
