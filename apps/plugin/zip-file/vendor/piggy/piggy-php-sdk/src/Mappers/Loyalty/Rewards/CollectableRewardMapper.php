<?php

namespace Piggy\Api\Mappers\Loyalty\Rewards;

use Exception;
use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Mappers\Contacts\ContactMapper;
use Piggy\Api\Models\Loyalty\Rewards\CollectableReward;
use stdClass;

class CollectableRewardMapper extends BaseMapper
{
    /**
     * @throws Exception
     */
    public function map(stdClass $data): CollectableReward
    {
        $contactMapper = new ContactMapper();
        $contact = $contactMapper->map($data->contact);

        $rewardMapper = new RewardMapper();
        $reward = $rewardMapper->map($data->reward);

        return new CollectableReward(
            $contact,
            $this->parseDate($data->created_at),
            $data->uuid,
            $data->title,
            $reward,
            $data->expires_at ? $this->parseDate($data->expires_at) : null,
            $data->has_been_collected
        );
    }
}
