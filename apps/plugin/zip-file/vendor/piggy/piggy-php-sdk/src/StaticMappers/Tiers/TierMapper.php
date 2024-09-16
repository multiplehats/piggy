<?php

namespace Piggy\Api\StaticMappers\Tiers;

use Piggy\Api\Models\Tiers\Tier;

class TierMapper
{
    public static function map($data): Tier
    {
        return new Tier(
            $data->name,
            $data->position,
            $data->uuid ?? null,
            $data->description ?? null,
            $data->media ? get_object_vars($data->media) : null
        );
    }
}
