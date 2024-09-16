<?php

namespace Piggy\Api\StaticMappers\Loyalty\Receptions;

use Piggy\Api\Models\Loyalty\Receptions\DigitalRewardReception;
use Piggy\Api\StaticMappers\BaseMapper;
use Piggy\Api\StaticMappers\ContactIdentifiers\ContactIdentifierMapper;
use Piggy\Api\StaticMappers\Contacts\ContactMapper;
use Piggy\Api\StaticMappers\Loyalty\Rewards\DigitalRewardCodeMapper;
use Piggy\Api\StaticMappers\Loyalty\Rewards\DigitalRewardMapper;
use Piggy\Api\StaticMappers\Shops\ShopMapper;
use stdClass;

class DigitalRewardReceptionMapper extends BaseMapper
{
    public static function map(stdClass $data): DigitalRewardReception
    {
        $contact = ContactMapper::map($data->contact);
        $shop = ShopMapper::map($data->shop);

        $digitalReward = null;
        if (isset($data->digital_reward)) {
            $digitalReward = DigitalRewardMapper::map($data->digital_reward);
        }

        if (isset($data->digital_reward_code)) {
            $digitalRewardCode = DigitalRewardCodeMapper::map($data->digital_reward_code);
        }

        if (isset($data->contact_identifier)) {
            $contactIdentifier = ContactIdentifierMapper::map($data->contact_identifier);
        }

        return new DigitalRewardReception(
            $data->type,
            $data->credits,
            $data->uuid,
            $contact,
            $shop,
            $data->channel,
            $contactIdentifier,
            self::parseDate($data->created_at),
            $data->title,
            $digitalReward ?? null,
            $digitalRewardCode ?? null
        );
    }
}
