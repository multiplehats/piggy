<?php

namespace Piggy\Api\Mappers\Loyalty\Rewards;

use Exception;
use Piggy\Api\Models\Loyalty\Rewards\CollectableReward;

class CollectableRewardsMapper
{
    /**
     * @param  mixed[]  $data
     * @return CollectableReward[]
     *
     * @throws Exception
     */
    public function map(array $data): array
    {
        $mapper = new CollectableRewardMapper();

        $collectableRewards = [];
        foreach ($data as $item) {
            $collectableRewards[] = $mapper->map($item);
        }

        return $collectableRewards;
    }
}
