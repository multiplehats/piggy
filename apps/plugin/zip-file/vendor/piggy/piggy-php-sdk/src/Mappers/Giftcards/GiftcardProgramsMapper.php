<?php

namespace Piggy\Api\Mappers\Giftcards;

use Piggy\Api\Models\Giftcards\GiftcardProgram;
use stdClass;

class GiftcardProgramsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return GiftcardProgram[]
     */
    public function map(array $data): array
    {
        $mapper = new GiftcardProgramMapper();

        $giftcardPrograms = [];

        foreach ($data as $item) {
            $giftcardPrograms[] = $mapper->map($item);
        }

        return $giftcardPrograms;
    }
}
