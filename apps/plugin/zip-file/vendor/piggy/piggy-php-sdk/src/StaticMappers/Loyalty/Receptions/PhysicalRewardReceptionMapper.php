<?php

namespace Piggy\Api\StaticMappers\Loyalty\Receptions;

use Piggy\Api\Models\Loyalty\Receptions\PhysicalRewardReception;
use Piggy\Api\StaticMappers\BaseMapper;
use Piggy\Api\StaticMappers\ContactIdentifiers\ContactIdentifierMapper;
use Piggy\Api\StaticMappers\Contacts\ContactMapper;
use Piggy\Api\StaticMappers\Loyalty\Rewards\PhysicalRewardMapper;
use Piggy\Api\StaticMappers\Shops\ShopMapper;
use stdClass;

class PhysicalRewardReceptionMapper extends BaseMapper
{
    public static function map(stdClass $data): PhysicalRewardReception
    {
        $contact = ContactMapper::map($data->contact);
        $shop = ShopMapper::map($data->shop);
        $physicalReward = PhysicalRewardMapper::map($data->reward);

        $contactIdentifier = null;
        if (isset($data->contact_identifier)) {
            $contactIdentifier = ContactIdentifierMapper::map($data->contact_identifier);
        }

        return new PhysicalRewardReception(
            $data->type,
            $data->credits,
            $data->uuid,
            $contact,
            $shop,
            $data->channel,
            $contactIdentifier,
            self::parseDate($data->created_at),
            $data->title,
            $physicalReward,
            isset($data->expiration_date) ? self::parseDate($data->expiration_date) : null,
            $data->has_been_collected
        );
    }
}
