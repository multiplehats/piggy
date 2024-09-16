<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\SubscriptionType;
use stdClass;

class SubscriptionTypesMapper
{
    /**
     * @param  stdClass[]  $data
     * @return SubscriptionType[]
     */
    public function map(array $data): array
    {
        $mapper = new SubscriptionTypeMapper();
        $subscriptionTypes = [];

        foreach ($data as $item) {
            $subscriptionTypes[] = $mapper->map($item);
        }

        return $subscriptionTypes;
    }
}
