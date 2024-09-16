<?php

namespace Piggy\Api\StaticMappers\Vouchers;

class PromotionAttributesMapper
{
    public static function map($data): array
    {
        $attributes = [];

        foreach ($data as $item) {
            $attributes[] = PromotionAttributeMapper::map($item);
        }

        return $attributes;
    }
}
