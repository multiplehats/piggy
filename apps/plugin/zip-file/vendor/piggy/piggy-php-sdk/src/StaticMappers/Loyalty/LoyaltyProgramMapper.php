<?php

namespace Piggy\Api\StaticMappers\Loyalty;

use Piggy\Api\Models\Loyalty\LoyaltyProgram;
use stdClass;

class LoyaltyProgramMapper
{
    public static function map(stdClass $data): LoyaltyProgram
    {
        return new LoyaltyProgram(
            $data->id,
            $data->name,
            $data->max_amount ?? '',
            $data->custom_credit_name ?? ''
        );
    }
}
