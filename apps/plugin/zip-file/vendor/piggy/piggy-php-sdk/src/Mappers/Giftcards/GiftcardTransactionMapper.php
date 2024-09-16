<?php

namespace Piggy\Api\Mappers\Giftcards;

use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Models\Giftcards\GiftcardTransaction;
use stdClass;

class GiftcardTransactionMapper extends BaseMapper
{
    public function map(stdClass $data): GiftcardTransaction
    {
        if (isset($data->settlements)) {
            $giftcardTransactionSettlementMapper = new GiftcardTransactionSettlementMapper();
            $settlements = array_map(function ($settlement) use ($giftcardTransactionSettlementMapper) {
                return $giftcardTransactionSettlementMapper->map($settlement);
            }, $data->settlements);
        }

        return new GiftcardTransaction(
            $data->uuid,
            $data->amount_in_cents,
            $this->parseDate($data->created_at),
            $data->type ?? null,
            $data->settled ?? null,
            $data->card_id ?? null,
            $data->shop_id ?? null,
            $settlements ?? [],
            $data->id ?? null
        );
    }
}
