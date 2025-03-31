<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Giftcards schema class.
 *
 * @internal
 */
class GiftcardsSchema extends AbstractSchema
{
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'giftcards';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'giftcards';

	/**
	 * Coupons schema properties.
	 *
	 * @return array
	 */
	public function get_properties()
	{
		return [
			'balance'          => [
				'type' => 'number',
			],
		];
	}

	/**
	 * Convert a Giftcard coupon into an object suitable for the response.
	 *
	 * @param \WC_Coupon $coupon The giftcard coupon.
	 * @return array
	 */
	public function get_item_response($coupon)
	{
		$amount = $coupon->get_amount();

		return [
			'balance' => wc_price($amount),
			'balance_raw' => $amount,
		];
	}
}
