<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Models\Contacts\Attribute;
use stdClass;

class AttributeMapper extends BaseMapper
{
    public function map(stdClass $data): Attribute
    {
        $fieldType = $data->type;
        $isSoftReadOnly = property_exists($data, 'is_soft_read_only') && $data->is_soft_read_only;
        $isHardReadOnly = property_exists($data, 'is_hard_read_only') && $data->is_hard_read_only;
        $isPiggyDefined = property_exists($data, 'is_piggy_defined') && $data->is_piggy_defined;

        $options = [];

        if (property_exists($data, 'options') && $data->options != null) {
            foreach ($data->options as $item) {
                $options[] = get_object_vars($item);
            }
        }

        return new Attribute(
            $data->name,
            $data->label,
            $data->type,
            $fieldType,
            $data->description ?? null,
            $isSoftReadOnly,
            $isHardReadOnly,
            $isPiggyDefined,
            $options
        );
    }
}
