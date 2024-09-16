<?php

namespace Piggy\Api\StaticMappers\Contacts;

class AttributesMapper
{
    public static function map($data): array
    {
        $attributes = [];
        foreach ($data as $item) {
            $attributes[] = AttributeMapper::map($item);
        }

        return $attributes;
    }
}
