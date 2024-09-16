<?php

namespace Piggy\Api\StaticMappers\Loyalty\Rewards;

use Exception;

class CollectableRewardsMapper
{
    /**
     * @throws Exception
     */
    public static function map($data): array
    {
        $collectableRewards = [];
        foreach ($data as $item) {
            $collectableRewards[] = CollectableRewardMapper::map($item);
        }

        return $collectableRewards;
    }
}
