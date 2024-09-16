<?php

namespace Piggy\Api\Mappers\Giftcards;

use Piggy\Api\Models\Giftcards\GiftcardProgram;
use stdClass;

class GiftcardProgramMapper
{
    public function map(stdClass $data): GiftcardProgram
    {
        return new GiftcardProgram(
            $data->uuid,
            $data->name,
            $data->active ?? true
        );
    }
}
