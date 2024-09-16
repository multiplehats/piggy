<?php

namespace Piggy\Api\StaticMappers\Loyalty\Rewards;

use Piggy\Api\Models\Loyalty\Rewards\DigitalRewardCode;

class DigitalRewardCodeMapper
{
    public static function map($data): DigitalRewardCode
    {
        return new DigitalRewardCode(
            $data->code
        );
    }
}
