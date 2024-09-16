<?php

namespace Piggy\Api\Mappers\Loyalty\Rewards;

use Piggy\Api\Enum\RewardType;
use Piggy\Api\Mappers\Loyalty\MediaMapper;
use Piggy\Api\Mappers\Loyalty\RewardAttributes\RewardAttributesMapper;
use Piggy\Api\Models\Loyalty\Rewards\DigitalReward;
use stdClass;

class DigitalRewardMapper
{
    public function map(stdClass $data): DigitalReward
    {
        $mediaMapper = new MediaMapper();

        if (isset($data->media)) {
            $media = $mediaMapper->map($data->media);
        }

        $active = property_exists($data, 'active') ? $data->active : true;

        $attributesNamesToDelete = ['uuid', 'title', 'description', 'required_credits', 'reward_type', 'media', 'active', 'is_active', 'id', 'stock', 'cost_price'];
        $attributes = array_diff_key(get_object_vars($data), array_flip($attributesNamesToDelete));

        if (property_exists($data, 'attributes')) {
            $attributesMapper = new RewardAttributesMapper();
            $attributes = $attributesMapper->map($data->$attributes);
        }

        return new DigitalReward(
            $data->uuid,
            $data->title ?? '',
            $data->required_credits ?? null,
            $media ?? null,
            $data->description ?? '',
            $active,
            RewardType::byName($data->reward_type)->getValue() ?? null,
            $attributes
        );
    }
}
