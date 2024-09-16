<?php

namespace Piggy\Api\StaticMappers\Contacts;

class ContactsMapper
{
    public static function map($data): array
    {
        $contacts = [];
        foreach ($data as $item) {
            $contacts[] = ContactMapper::map($item);
        }

        return $contacts;
    }
}
