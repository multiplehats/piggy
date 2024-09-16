<?php

namespace Piggy\Api\StaticMappers\Prepaid;

use Piggy\Api\Models\Prepaid\PrepaidTransaction;
use Piggy\Api\StaticMappers\BaseMapper;
use stdClass;

class PrepaidTransactionMapper extends BaseMapper
{
    public static function map(stdClass $data): PrepaidTransaction
    {
        $prepaidBalance = PrepaidBalanceMapper::map($data->prepaid_balance);

        return new PrepaidTransaction(
            $data->amount_in_cents,
            $prepaidBalance,
            $data->uuid,
            self::parseDate($data->created_at)
        );
    }
}
