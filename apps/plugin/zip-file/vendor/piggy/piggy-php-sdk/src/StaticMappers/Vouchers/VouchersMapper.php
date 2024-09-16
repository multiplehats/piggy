<?php

namespace Piggy\Api\StaticMappers\Vouchers;

class VouchersMapper
{
    public static function map($data): array
    {
        $vouchers = [];

        foreach ($data as $item) {
            $vouchers[] = VoucherMapper::map($item);
        }

        return $vouchers;
    }
}
