<?php

namespace Piggy\Api\Mappers\Tiers;

use Piggy\Api\Models\Tiers\Tier;
use stdClass;

class TiersMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Tier[]
     */
    public function map(array $data): array
    {
        $mapper = new TierMapper();

        $tiers = [];
        foreach ($data as $item) {
            $tiers[] = $mapper->map($item);
        }

        return $tiers;
    }
}
