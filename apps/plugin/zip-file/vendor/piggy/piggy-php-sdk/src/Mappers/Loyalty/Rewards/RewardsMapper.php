<?php

namespace Piggy\Api\Mappers\Loyalty\Rewards;

use Exception;
use Piggy\Api\Models\Loyalty\Rewards\Reward;

class RewardsMapper
{
    /**
     * @param  mixed[]  $data
     * @return Reward[]
     *
     * @throws Exception
     */
    public function map(array $data): array
    {
        $mapper = new RewardMapper();

        $rewards = [];
        foreach ($data as $rewardData) {
            $rewards[] = $mapper->map($rewardData);
        }

        return $rewards;
    }
}
