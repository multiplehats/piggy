<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\Contact;
use stdClass;

class ContactsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Contact[]
     */
    public function map(array $data): array
    {
        $contactMapper = new ContactMapper;

        $contacts = [];
        foreach ($data as $item) {
            $contacts[] = $contactMapper->map($item);
        }

        return $contacts;
    }
}
