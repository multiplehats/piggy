<?php

namespace Piggy\Api\Mappers\Vouchers;

use Piggy\Api\Models\Vouchers\Voucher;
use stdClass;

class VouchersMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Voucher[]
     */
    public function map(array $data): array
    {
        $mapper = new VoucherMapper();

        $vouchers = [];

        foreach ($data as $item) {
            $vouchers[] = $mapper->map($item);
        }

        return $vouchers;
    }
}
