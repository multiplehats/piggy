<?php

namespace Piggy\Api\Mappers\Vouchers;

use Piggy\Api\Models\Vouchers\VoucherLock;
use stdClass;

class VoucherLockMapper
{
    public function map(stdClass $data): VoucherLock
    {
        $voucherMapper = new VoucherMapper();
        $voucher = $voucherMapper->map($data->voucher);

        $lockMapper = new LockMapper();
        $lock = $lockMapper->map($data->lock);

        return new VoucherLock(
            $voucher,
            $lock
        );
    }
}
