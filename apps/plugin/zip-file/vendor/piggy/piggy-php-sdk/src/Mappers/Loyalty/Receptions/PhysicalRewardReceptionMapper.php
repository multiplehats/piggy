<?php

namespace Piggy\Api\Mappers\Loyalty\Receptions;

use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Mappers\ContactIdentifiers\ContactIdentifierMapper;
use Piggy\Api\Mappers\Contacts\ContactMapper;
use Piggy\Api\Mappers\Loyalty\Rewards\PhysicalRewardMapper;
use Piggy\Api\Mappers\Shops\ShopMapper;
use Piggy\Api\Models\Loyalty\Receptions\PhysicalRewardReception;
use stdClass;

class PhysicalRewardReceptionMapper extends BaseMapper
{
    public function map(stdClass $data): PhysicalRewardReception
    {
        $contactMapper = new ContactMapper();
        $physicalRewardMapper = new PhysicalRewardMapper();
        $shopMapper = new ShopMapper();
        $contactIdentifierMapper = new ContactIdentifierMapper();

        $contact = $contactMapper->map($data->contact);
        $shop = $shopMapper->map($data->shop);
        $physicalReward = $physicalRewardMapper->map($data->reward);

        if (isset($data->contact_identifier)) {
            $contactIdentifier = $contactIdentifierMapper->map($data->contact_identifier);
        } else {
            $contactIdentifier = null;
        }

        return new PhysicalRewardReception(
            $data->type,
            $data->credits,
            $data->uuid,
            $contact,
            $shop,
            $data->channel,
            $contactIdentifier,
            $this->parseDate($data->created_at),
            $data->title,
            $physicalReward,
            isset($data->expires_at) ? $this->parseDate($data->expires_at) : null,
            $data->has_been_collected
        );
    }
}
