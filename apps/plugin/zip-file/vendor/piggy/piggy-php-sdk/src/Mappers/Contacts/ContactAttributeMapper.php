<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\ContactAttribute;
use stdClass;

class ContactAttributeMapper
{
    public function map(stdClass $data): ContactAttribute
    {
        $attribute = null;
        if (property_exists($data, 'attribute')) {
            $attributeMapper = new AttributeMapper();
            $attribute = $attributeMapper->map($data->attribute);
        }

        return new ContactAttribute(
            $data->value,
            $attribute ?? null
        );
    }
}
