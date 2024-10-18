<?php
namespace Leat\Api\Schemas\V1\Admin;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Settings class.
 *
 * @internal
 */
class SettingsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'settings';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'settings';

	/**
	 * API key schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'reward_order_statuses' => [
				'description' => __( 'Reward order statuses.', 'leat' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'paid' => [
						'type'        => 'string',
						'description' => __( 'Pending payment.', 'leat' ),
					],
					'pending' => [
						'type'        => 'string',
						'description' => __( 'On hold.', 'leat' ),
					],
					'processing' => [
						'type'        => 'string',
						'description' => __( 'Processing.', 'leat' ),
					],
					'completed' => [
						'type'        => 'string',
						'description' => __( 'Completed.', 'leat' ),
					],
				],
			],
		];
	}

	/**
	 * Get the schema item identifier.
	 *
	 * @return string
	 */
	public function get_item_response( $id ) {
		return $this->settings->get_item_response($id);

		return $item;
	}
}
