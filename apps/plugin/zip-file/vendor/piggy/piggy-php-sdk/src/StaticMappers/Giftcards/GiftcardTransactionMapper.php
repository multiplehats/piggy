<?php

namespace Piggy\Api\StaticMappers\Giftcards;

use Piggy\Api\Models\Giftcards\GiftcardTransaction;
use Piggy\Api\StaticMappers\BaseMapper;
use Piggy\Api\StaticMappers\Shops\ShopMapper;
use stdClass;

class GiftcardTransactionMapper extends BaseMapper
{
    public static function map(stdClass $data): GiftcardTransaction
    {
        if (! empty($data->settlements)) {
            $settlements = array_map(function ($settlement) {
                return GiftcardTransactionSettlementMapper::map($settlement);
            }, $data->settlements);
        }

        $shop = null;
        if (isset($data->shop)) {
            $shop = ShopMapper::map($data->shop);
        }

        $card = null;
        if (isset($data->card)) {
            $card = new stdClass();
            $card->id = $data->card->id ?? null;
            $card->uuid = $data->card->uuid ?? null;
        }

        return new GiftcardTransaction(
            $data->uuid,
            $data->amount_in_cents,
            self::parseDate($data->created_at),
            $data->type ?? null,
            $data->settled ?? null,
            $card->id ?? null,
            $shop->getId() ?? null,
            $settlements ?? [],
            $data->id ?? null,
            $shop,
            $card
        );
    }
}
