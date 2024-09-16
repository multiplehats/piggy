<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\Subscription;
use stdClass;

class SubscriptionsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Subscription[]
     */
    public function map(array $data): array
    {
        $mapper = new SubscriptionMapper();

        $subscriptions = [];
        foreach ($data as $item) {
            $subscriptions[] = $mapper->map($item);
        }

        return $subscriptions;
    }
}
