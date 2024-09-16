<?php

namespace Piggy\Api\Mappers\Loyalty;

use Piggy\Api\Models\Loyalty\CreditBalance;
use stdClass;

class CreditBalanceMapper
{
    public function map(stdClass $data): CreditBalance
    {
        return new CreditBalance($data->balance);
    }
}
