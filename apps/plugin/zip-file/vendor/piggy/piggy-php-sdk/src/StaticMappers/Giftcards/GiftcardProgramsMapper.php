<?php

namespace Piggy\Api\StaticMappers\Giftcards;

class GiftcardProgramsMapper
{
    public static function map($data): array
    {
        $giftcardPrograms = [];

        foreach ($data as $item) {
            $giftcardPrograms[] = GiftcardProgramMapper::map($item);
        }

        return $giftcardPrograms;
    }
}
