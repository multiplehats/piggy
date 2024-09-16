<?php

namespace Piggy\Api\Mappers\Loyalty;

use Piggy\Api\Models\Loyalty\LoyaltyProgram;
use stdClass;

class LoyaltyProgramMapper
{
    public function map(stdClass $data): LoyaltyProgram
    {
        return new LoyaltyProgram(
            $data->id,
            $data->name,
            $data->max_amount ?? '',
            $data->custom_credit_name ?? ''
        );
    }
}
