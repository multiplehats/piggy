<?php
namespace PiggyWP\Api\Schemas\V1\Admin;

use PiggyWP\Api\Schemas\V1\AbstractSchema;

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
				'description' => __( 'Reward order statuses.', 'piggy' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'paid' => [
						'type'        => 'string',
						'description' => __( 'Pending payment.', 'piggy' ),
					],
					'pending' => [
						'type'        => 'string',
						'description' => __( 'On hold.', 'piggy' ),
					],
					'processing' => [
						'type'        => 'string',
						'description' => __( 'Processing.', 'piggy' ),
					],
					'completed' => [
						'type'        => 'string',
						'description' => __( 'Completed.', 'piggy' ),
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
	public function get_item_response( $item ) {
		$default = isset( $item['default'] ) ? $item['default'] : null;
		$id = $item['id'];

		$item['value'] = get_option('piggy_' . $id, $default);

		if( $item['type'] === 'translatable_text' && is_string($item['value']) ) {
			$item['value'] = json_decode($item['value'], true);
		}

		if( $item['type'] === 'checkboxes' && is_string($item['value']) ) {
			$item['value'] = json_decode($item['value'], true);
		}

		return $item;
	}
}
