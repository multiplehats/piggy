<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\SubscriptionType;
use stdClass;

class SubscriptionTypeMapper
{
    public function map(stdClass $data): SubscriptionType
    {
        return new SubscriptionType(
            $data->uuid ?? '',
            $data->name,
            $data->description,
            $data->active,
            $data->strategy
        );
    }
}
