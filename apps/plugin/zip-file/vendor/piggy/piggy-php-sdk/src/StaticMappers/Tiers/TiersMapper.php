<?php

namespace Piggy\Api\StaticMappers\Tiers;

class TiersMapper
{
    public static function map($data): array
    {
        $tiers = [];
        foreach ($data as $item) {
            $tiers[] = TierMapper::map($item);
        }

        return $tiers;
    }
}
