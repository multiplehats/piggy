<?php

namespace Piggy\Api\Mappers\Units;

use Piggy\Api\Models\Loyalty\Unit;
use stdClass;

class UnitsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Unit[]
     */
    public function map(array $data): array
    {
        $mapper = new UnitMapper();

        $units = [];
        foreach ($data as $item) {
            $units[] = $mapper->map($item);
        }

        return $units;
    }
}
