<?php

namespace Piggy\Api\Mappers\ContactsPortal;

use Piggy\Api\Models\ContactsPortal\ContactsPortalAuthUrl;
use stdClass;

class ContactsPortalAuthUrlMapper
{
    public function map(stdClass $data): ContactsPortalAuthUrl
    {
        return new ContactsPortalAuthUrl(
            $data->url
        );
    }
}
