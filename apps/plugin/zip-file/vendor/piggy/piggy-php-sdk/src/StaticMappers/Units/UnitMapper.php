<?php

namespace Piggy\Api\StaticMappers\Units;

use Piggy\Api\Models\Loyalty\Unit;
use stdClass;

class UnitMapper
{
    public static function map(stdClass $data): Unit
    {
        return new Unit(
            $data->name,
            $data->label ?? null,
            $data->is_default ?? null,
            $data->prefix ?? null
        );
    }
}
