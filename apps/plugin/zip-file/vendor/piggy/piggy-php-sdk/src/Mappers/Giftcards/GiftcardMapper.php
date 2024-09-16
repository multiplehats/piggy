<?php

namespace Piggy\Api\Mappers\Giftcards;

use Piggy\Api\Enum\GiftcardType;
use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Models\Giftcards\Giftcard;
use stdClass;

class GiftcardMapper extends BaseMapper
{
    public function map(stdClass $data): Giftcard
    {
        if (isset($data->giftcard_program)) {
            $giftcardProgramMapper = new GiftcardProgramMapper();
            $giftcardProgram = $giftcardProgramMapper->map($data->giftcard_program);
        }

        if (isset($data->expiration_date) && ! empty($data->expiration_date)) {
            $expirationDate = $this->parseDate($data->expiration_date);
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
