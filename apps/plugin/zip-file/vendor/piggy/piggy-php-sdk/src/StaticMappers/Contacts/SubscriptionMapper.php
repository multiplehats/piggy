<?php

namespace Piggy\Api\StaticMappers\Contacts;

use Piggy\Api\Models\Contacts\Subscription;
use stdClass;

class SubscriptionMapper
{
    public static function map(stdClass $data): Subscription
    {
        $subscriptionType = SubscriptionTypeMapper::map($data->subscription_type);

        return new Subscription(
            $subscriptionType,
            $data->is_subscribed,
            $data->status
        );
    }
}
