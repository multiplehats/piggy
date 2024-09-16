<?php

namespace Piggy\Api\Mappers\Giftcards;

use Piggy\Api\Models\Giftcards\GiftcardTransactionSettlement;
use stdClass;

class GiftcardTransactionSettlementMapper
{
    public function map(stdClass $data): GiftcardTransactionSettlement
    {
        return new GiftcardTransactionSettlement(
            $data->id ?? null
        );
    }
}
