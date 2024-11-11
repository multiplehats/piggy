<?php
namespace Leat\Api\Schemas\V1\Admin;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Shops class.
 *
 * @internal
 */
class ShopsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'shops';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shops';

	/**
	 * Shops schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'uuid'          => [
				'description' => __( 'The shops\'s unique id.', 'leat-crm' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'name'          => [
				'description' => __( 'The shops\'s name.', 'leat-crm' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
		];
	}
}
