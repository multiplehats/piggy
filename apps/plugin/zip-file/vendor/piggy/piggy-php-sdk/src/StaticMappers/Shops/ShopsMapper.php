<?php

namespace Piggy\Api\StaticMappers\Shops;

class ShopsMapper
{
    public static function map($data): array
    {
        $shops = [];
        foreach ($data as $item) {
            $shops[] = ShopMapper::map($item);
        }

        return $shops;
    }
}
