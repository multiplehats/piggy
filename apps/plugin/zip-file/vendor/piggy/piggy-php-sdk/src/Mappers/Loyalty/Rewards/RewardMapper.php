<?php

namespace Piggy\Api\Mappers\Loyalty\Rewards;

use Exception;
use Piggy\Api\Enum\RewardType;
use Piggy\Api\Models\Loyalty\Rewards\DigitalReward;
use Piggy\Api\Models\Loyalty\Rewards\PhysicalReward;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use stdClass;

class RewardMapper
{
    /**
     * @return DigitalReward|PhysicalReward
     *
     * @throws Exception
     */
    public function map(stdClass $data): Reward
    {
        $physicalMapper = new PhysicalRewardMapper();
        $digitalMapper = new DigitalRewardMapper();

        $reward = null;

        if ($data->reward_type === RewardType::PHYSICAL) {
            $reward = $physicalMapper->map($data);
        }

        if ($data->reward_type === RewardType::DIGITAL) {
            $reward = $digitalMapper->map($data);
        }

        if ($reward === null) {
            throw new Exception('Reward could not be mapped. No valid reward type given');
        }

        return $reward;
    }
}
