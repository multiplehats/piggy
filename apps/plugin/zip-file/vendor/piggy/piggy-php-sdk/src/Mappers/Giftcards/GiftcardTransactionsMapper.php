<?php

namespace Piggy\Api\Mappers\Giftcards;

use Piggy\Api\Models\Giftcards\GiftcardTransaction;
use stdClass;

class GiftcardTransactionsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return GiftcardTransaction[]
     */
    public function map(array $data): array
    {
        $giftcardTransactionsMapper = new GiftcardTransactionMapper;

        $giftcardTransactions = [];

        foreach ($data as $item) {
            $giftcardTransactions[] = $giftcardTransactionsMapper->map($item);
        }

        return $giftcardTransactions;
    }
}
