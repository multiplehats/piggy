<?php
namespace Leat\Api\Schemas\V1\Admin;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Rewards class.
 *
 * @internal
 */
class RewardsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'rewards';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'rewards';

	/**
	 * Rewards schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'uuid'            => [
				'description' => __( 'The reward\'s unique id.', 'leat-crm' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'name'            => [
				'description' => __( 'The reward\'s name.', 'leat-crm' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'requiredCredits' => [
				'description' => __( 'The reward\'s required credits.', 'leat-crm' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit' ],
			],
			'type'            => [
				'description' => __( 'The reward\'s type.', 'leat-crm' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'active'          => [
				'description' => __( 'The reward\'s active status.', 'leat-crm' ),
				'type'        => 'boolean',
				'context'     => [ 'view', 'edit' ],
			],
			'attributes'      => [
				'description' => __( 'The reward\'s attributes.', 'leat-crm' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
			],
		];
	}
}
