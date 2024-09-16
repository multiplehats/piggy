<?php

namespace Piggy\Api\StaticMappers\Giftcards;

use Piggy\Api\Enum\GiftcardType;
use Piggy\Api\Models\Giftcards\Giftcard;
use Piggy\Api\StaticMappers\BaseMapper;
use stdClass;

class GiftcardMapper extends BaseMapper
{
    public static function map(stdClass $data): Giftcard
    {
        if (isset($data->giftcard_program)) {
            $giftcardProgramMapper = new GiftcardProgramMapper();
            $giftcardProgram = $giftcardProgramMapper->map($data->giftcard_program);
        }

        if (isset($data->expiration_date)) {
            $expirationDate = self::parseDate($data->expiration_date);
        }

        return new Giftcard(
            $data->uuid,
            $data->hash,
            $data->amount_in_cents,
            GiftcardType::byName($data->type)->getValue(),
            $data->active,
            $data->upgradeable,
            $giftcardProgram ?? null,
            $expirationDate ?? null,
            $data->id ?? null
        );
    }
}
