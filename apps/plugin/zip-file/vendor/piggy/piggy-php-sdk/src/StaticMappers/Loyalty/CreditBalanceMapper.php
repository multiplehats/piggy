<?php

namespace Piggy\Api\StaticMappers\Loyalty;

use Piggy\Api\Models\Loyalty\CreditBalance;
use stdClass;

class CreditBalanceMapper
{
    public static function map(stdClass $data): CreditBalance
    {
        return new CreditBalance($data->balance);
    }
}
