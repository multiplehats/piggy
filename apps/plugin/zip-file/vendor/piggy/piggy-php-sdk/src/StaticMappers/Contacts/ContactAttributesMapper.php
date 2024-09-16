<?php

namespace Piggy\Api\StaticMappers\Contacts;

class ContactAttributesMapper
{
    public static function map(array $data): array
    {
        $contactAttributes = [];

        foreach ($data as $item) {
            $contactAttributes[] = ContactAttributeMapper::map($item);
        }

        return $contactAttributes;
    }
}
