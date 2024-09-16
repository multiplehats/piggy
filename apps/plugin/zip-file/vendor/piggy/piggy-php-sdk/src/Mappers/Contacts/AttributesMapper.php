<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\Attribute;
use stdClass;

class AttributesMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Attribute[]
     */
    public function map(array $data): array
    {
        $attributeMapper = new AttributeMapper;

        $attributes = [];
        foreach ($data as $item) {
            $attributes[] = $attributeMapper->map($item);
        }

        return $attributes;
    }
}
