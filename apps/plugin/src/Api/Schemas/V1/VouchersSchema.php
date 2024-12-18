<?php

namespace Leat\Api\Schemas\V1;

use Leat\Api\Schemas\V1\AbstractSchema;

/**
 * Vouchers schema class.
 *
 * @internal
 */
class VouchersSchema extends AbstractSchema {
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'vouchers';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'vouchers';

    /**
     * Vouchers schema properties.
     *
     * @return array
     */
    public function get_properties() {
        return [
            'vouchers' => [
                'description' => __( 'List of voucher codes', 'leat-crm' ),
                'type'        => 'array',
                'items'       => [
                    'type' => 'string',
                ],
            ],
        ];
    }

    /**
	 * Convert a Voucher into an object suitable for the response.
	 *
	 * @param array $voucher Voucher object.
	 * @return array
	 */
	public function get_item_response($voucher) {
        return $voucher;
    }
}