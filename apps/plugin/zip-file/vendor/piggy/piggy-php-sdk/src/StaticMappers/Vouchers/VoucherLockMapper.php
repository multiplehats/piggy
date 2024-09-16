<?php

namespace Piggy\Api\StaticMappers\Vouchers;

use Piggy\Api\Models\Vouchers\VoucherLock;

class VoucherLockMapper
{
    public static function map($data): VoucherLock
    {
        $voucher = VoucherMapper::map($data->voucher);
        $lock = LockMapper::map($data->lock);

        return new VoucherLock(
            $voucher,
            $lock
        );
    }
}
