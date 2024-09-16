<?php

namespace Piggy\Api\StaticMappers\CustomAttributes;

use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use stdClass;

class CustomAttributeMapper
{
    public static function map(stdClass $data): CustomAttribute
    {
        $group = null;

        if (isset($data->group)) {
            $group = GroupMapper::map($data->group);
        }

        return new CustomAttribute(
            $data->id,
            $data->entity,
            $data->name,
            $data->label,
            $data->type,
            $data->is_piggy_defined,
            $data->is_soft_read_only,
            $data->is_hard_read_only,
            $data->has_unique_value,
            $data->description,
            $data->options,
            $data->position,
            $data->created_at,
            $data->can_be_deleted,
            $data->meta,
            $data->group_name ?? null,
            $group,
            $data->field_type ?? null,
            $data->last_used_date ?? null,
            $data->created_at ?? null
        );
    }
}
