<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Coupons schema class.
 *
 * @internal
 */
class CouponsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'coupons';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'coupons';

	/**
	 * Coupons schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'coupons' => [
				'description' => __( 'List of coupon codes', 'leat-crm' ),
				'type'        => 'array',
				'items'       => [
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * Convert a Coupon into an object suitable for the response.
	 *
	 * @param array $coupon Coupon object.
	 * @return array
	 */
	public function get_item_response( $coupon ) {
		return $coupon;
	}
}
