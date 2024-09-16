<?php

namespace Piggy\Api\StaticMappers\Loyalty\Receptions;

use Piggy\Api\Enum\LoyaltyTransactionType;
use Piggy\Api\Models\Loyalty\Receptions\DigitalRewardReception;
use Piggy\Api\Models\Loyalty\Receptions\PhysicalRewardReception;
use stdClass;

class RewardReceptionMapper
{
    /**
     * @return DigitalRewardReception|PhysicalRewardReception|null
     */
    public static function map(stdClass $data)
    {
        $rewardReception = null;

        if ($data->type === LoyaltyTransactionType::PHYSICAL_REWARD_RECEPTION) {
            $rewardReception = PhysicalRewardReceptionMapper::map($data);
        }

        if ($data->type === LoyaltyTransactionType::DIGITAL_REWARD_RECEPTION) {
            $rewardReception = DigitalRewardReceptionMapper::map($data);
        }

        return $rewardReception;
    }
}
