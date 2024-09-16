<?php

namespace Piggy\Api\StaticMappers\Loyalty\Rewards;

use Exception;
use Piggy\Api\Models\Loyalty\Rewards\CollectableReward;
use Piggy\Api\StaticMappers\BaseMapper;
use Piggy\Api\StaticMappers\Contacts\ContactMapper;
use stdClass;

class CollectableRewardMapper extends BaseMapper
{
    /**
     * @throws Exception
     */
    public static function map(stdClass $data): CollectableReward
    {
        $contact = ContactMapper::map($data->contact);
        $reward = RewardMapper::map($data->reward);

        return new CollectableReward(
            $contact,
            self::parseDate($data->created_at),
            $data->uuid,
            $data->title,
            $reward,
            self::parseDate($data->expires_at),
            $data->has_been_collected
        );
    }
}
