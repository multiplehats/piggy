<?php

namespace Piggy\Api\StaticMappers\Contacts;

class SubscriptionsMapper
{
    public static function map($data): array
    {
        $subscriptions = [];
        foreach ($data as $item) {
            $subscriptions[] = SubscriptionMapper::map($item);
        }

        return $subscriptions;
    }
}
