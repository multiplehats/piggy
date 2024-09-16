<?php

namespace Piggy\Api\Mappers\CustomAttributes;

use Piggy\Api\Models\CustomAttributes\Group;
use stdClass;

class GroupMapper
{
    public function map(stdClass $data): Group
    {
        return new Group(
            $data->name,
            $data->position,
            $data->label,
        );
    }
}
