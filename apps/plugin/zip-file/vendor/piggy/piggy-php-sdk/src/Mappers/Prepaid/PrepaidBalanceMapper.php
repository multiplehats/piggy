<?php

namespace Piggy\Api\Mappers\Prepaid;

use Piggy\Api\Models\Prepaid\PrepaidBalance;
use stdClass;

class PrepaidBalanceMapper
{
    public function map(stdClass $data): PrepaidBalance
    {
        return new PrepaidBalance($data->balance_in_cents);
    }
}
