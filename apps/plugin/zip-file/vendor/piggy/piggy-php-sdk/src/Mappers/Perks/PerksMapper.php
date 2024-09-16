<?php

namespace Piggy\Api\Mappers\Perks;

use Piggy\Api\Models\Perks\Perk;
use stdClass;

class PerksMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Perk[]
     */
    public function map(array $data): array
    {
        $mapper = new PerkMapper();

        $perks = [];
        foreach ($data as $item) {
            $perks[] = $mapper->map($item);
        }

        return $perks;
    }
}
