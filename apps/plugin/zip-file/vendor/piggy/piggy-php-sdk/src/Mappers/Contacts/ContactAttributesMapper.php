<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\ContactAttribute;
use stdClass;

class ContactAttributesMapper
{
    /**
     * @param  stdClass[]  $data
     * @return ContactAttribute[]
     */
    public function map(array $data): array
    {
        $contactAttributeMapper = new ContactAttributeMapper;
        $contactAttributes = [];

        foreach ($data as $item) {
            $contactAttributes[] = $contactAttributeMapper->map($item);
        }

        return $contactAttributes;
    }
}
