<?php

namespace Piggy\Api\StaticMappers\Loyalty\Receptions;

use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\StaticMappers\BaseMapper;
use Piggy\Api\StaticMappers\ContactIdentifiers\ContactIdentifierMapper;
use Piggy\Api\StaticMappers\Contacts\ContactMapper;
use Piggy\Api\StaticMappers\Shops\ShopMapper;
use Piggy\Api\StaticMappers\Units\UnitMapper;
use stdClass;

class CreditReceptionMapper extends BaseMapper
{
    public static function map(stdClass $data): CreditReception
    {
        if (isset($data->contact)) {
            $contact = ContactMapper::map($data->contact);
        }

        if (isset($data->shop)) {
            $shop = ShopMapper::map($data->shop);
        }

        if (isset($data->unit)) {
            $unit = UnitMapper::map($data->unit);
        }

        if (isset($data->contact_identifier)) {
            $contactIdentifier = ContactIdentifierMapper::map($data->contact_identifier);
        }

        $attributes = [];

        foreach ($data as $propertyName => $value) {
            if (in_array($propertyName, ['type', 'credits', 'uuid', 'contact', 'shop', 'contact_identifier', 'created_at', 'unit_value', 'unit'])) {
                continue;
            }
            $attributes[$propertyName] = $value;
        }

        $attributes = [];

        foreach ($data as $propertyName => $value) {
            if (in_array($propertyName, ['type', 'credits', 'uuid', 'contact', 'shop', 'contact_identifier', 'created_at', 'unit_value', 'unit'])) {
                continue;
            }
            $attributes[$propertyName] = $value;
        }

        return new CreditReception(
            $data->type,
            $data->credits ?? null,
            $data->uuid,
            $contact ?? null,
            $shop ?? null,
            $data->channel,
            $contactIdentifier ?? null,
            self::parseDate($data->created_at),
            $data->unit_value ?? null,
            $unit ?? null,
            $attributes
        );
    }
}
