<?php

namespace Piggy\Api\StaticMappers\Giftcards;

class GiftcardTransactionsMapper
{
    public static function map(array $data): array
    {
        $giftcardTransactions = [];

        foreach ($data as $item) {
            $giftcardTransactions[] = GiftcardTransactionMapper::map($item);
        }

        return $giftcardTransactions;
    }
}
