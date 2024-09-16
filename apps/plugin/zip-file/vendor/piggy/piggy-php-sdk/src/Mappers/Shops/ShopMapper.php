<?php

namespace Piggy\Api\Mappers\Shops;

use Piggy\Api\Models\Shops\Shop;
use stdClass;

class ShopMapper
{
    public function map(stdClass $data): Shop
    {
        return new Shop(
            $data->uuid,
            $data->name,
            $data->id ?? null
        );
    }
}
