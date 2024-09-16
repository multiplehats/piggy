<?php

namespace Piggy\Api\Mappers\Loyalty\RewardAttributes;

use Piggy\Api\Models\Loyalty\RewardAttributes\RewardAttribute;
use stdClass;

class RewardAttributesMapper
{
    /**
     * @param  stdClass[]  $data
     * @return RewardAttribute[]
     */
    public function map(array $data): array
    {
        $rewardAttributeMapper = new RewardAttributeMapper;

        $rewardAttributes = [];

        foreach ($data as $item) {
            $rewardAttributes[] = $rewardAttributeMapper->map($item);
        }

        return $rewardAttributes;
    }
}
