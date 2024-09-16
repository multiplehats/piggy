<?php

namespace Piggy\Api\Mappers\Perks;

use Piggy\Api\Models\Perks\Perk;
use stdClass;

class PerkMapper
{
    public function map(stdClass $data): Perk
    {
        return new Perk(
            $data->uuid,
            $data->label,
            $data->name,
            $data->data_type,
            $data->options ?? []
        );
    }
}
