<?php

namespace Piggy\Api\StaticMappers\CustomAttributes;

use Piggy\Api\Models\CustomAttributes\Group;
use stdClass;

class GroupMapper
{
    public static function map(stdClass $data): Group
    {
        return new Group(
            $data->name,
            $data->position,
            $data->label,
        );
    }
}
