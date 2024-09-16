<?php

namespace Piggy\Api\StaticMappers\Vouchers;

class PromotionsMapper
{
    public static function map($data): array
    {
        $promotions = [];

        foreach ($data as $item) {
            $promotions[] = PromotionMapper::map($item);
        }

        return $promotions;
    }
}
