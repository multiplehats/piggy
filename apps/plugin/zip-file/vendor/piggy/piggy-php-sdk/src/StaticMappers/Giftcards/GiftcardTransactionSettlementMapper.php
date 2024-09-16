<?php

namespace Piggy\Api\StaticMappers\Giftcards;

use Piggy\Api\Models\Giftcards\GiftcardTransactionSettlement;
use stdClass;

class GiftcardTransactionSettlementMapper
{
    public static function map(stdClass $data): GiftcardTransactionSettlement
    {
        return new GiftcardTransactionSettlement(
            $data->id ?? null
        );
    }
}
