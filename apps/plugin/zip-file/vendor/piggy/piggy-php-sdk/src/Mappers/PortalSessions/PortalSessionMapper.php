<?php

namespace Piggy\Api\Mappers\PortalSessions;

use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Mappers\Contacts\ContactMapper;
use Piggy\Api\Mappers\Shops\ShopMapper;
use Piggy\Api\Models\PortalSessions\PortalSession;
use stdClass;

class PortalSessionMapper extends BaseMapper
{
    public function map(stdClass $data): PortalSession
    {
        $shopMapper = new ShopMapper();
        $shop = $shopMapper->map($data->shop);

        if (isset($data->contact)) {
            $contactMapper = new ContactMapper();
            $contact = $contactMapper->map($data->contact);
        }

        return new PortalSession(
            $data->url,
            $data->uuid,
            $shop,
            $this->parseDate($data->created_at),
            $contact ?? null
        );
    }
}
