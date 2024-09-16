<?php

namespace Piggy\Api\Mappers\Loyalty\Rewards;

use Piggy\Api\Models\Loyalty\Rewards\DigitalRewardCode;
use stdClass;

class DigitalRewardCodeMapper
{
    public function map(stdClass $data): DigitalRewardCode
    {
        return new DigitalRewardCode($data->code);
    }
}
