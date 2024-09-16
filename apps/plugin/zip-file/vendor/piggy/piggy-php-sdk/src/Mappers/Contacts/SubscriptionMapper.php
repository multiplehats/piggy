<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Models\Contacts\Subscription;
use stdClass;

class SubscriptionMapper
{
    public function map(stdClass $data): Subscription
    {
        $subscriptionTypeMapper = new SubscriptionTypeMapper();
        $subscriptionType = $subscriptionTypeMapper->map($data->subscription_type);

        return new Subscription(
            $subscriptionType,
            $data->is_subscribed,
            $data->status
        );
    }
}
