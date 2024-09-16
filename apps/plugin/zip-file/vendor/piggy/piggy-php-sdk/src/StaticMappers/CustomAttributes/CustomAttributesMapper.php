<?php

namespace Piggy\Api\StaticMappers\CustomAttributes;

class CustomAttributesMapper
{
    public static function map(array $data): array
    {
        $customAttributes = [];

        foreach ($data as $item) {
            $customAttributes[] = CustomAttributeMapper::map($item);
        }

        return $customAttributes;
    }
}
