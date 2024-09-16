<?php

namespace Piggy\Api\Mappers\Registers;

use Piggy\Api\Mappers\Shops\ShopMapper;
use Piggy\Api\Models\Registers\Register;
use stdClass;

class RegisterMapper
{
    public function map(stdClass $response): Register
    {
        $register = new Register();
        $shopMapper = new ShopMapper();

        $register->setId($response->id);
        $register->setName($response->name ?? null);
        $register->setShop($shopMapper->map($response->shop));
        $register->setIdentifier($response->identifier);

        return $register;
    }
}
