<?php

namespace Piggy\Api\StaticMappers\PortalSessions;

use Piggy\Api\Models\PortalSessions\PortalSession;
use Piggy\Api\StaticMappers\BaseMapper;
use Piggy\Api\StaticMappers\Contacts\ContactMapper;
use Piggy\Api\StaticMappers\Shops\ShopMapper;

class PortalSessionMapper extends BaseMapper
{
    public static function map($data): PortalSession
    {
        $shop = ShopMapper::map($data->shop);

        if (isset($data->contact)) {
            $contact = ContactMapper::map($data->contact);
        }

        return new PortalSession(
            $data->url,
            $data->uuid,
            $shop,
            self::parseDate($data->created_at),
            $contact ?? null
        );
    }
}
