<?php

namespace Piggy\Api\Mappers\WebhookSubscriptions;

use Piggy\Api\Models\WebhookSubscriptions\WebhookSubscription;
use stdClass;

class WebhookSubscriptionsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return WebhookSubscription[]
     */
    public function map(array $data): array
    {
        $mapper = new WebhookSubscriptionMapper();

        $WebhookSubscriptions = [];
        foreach ($data as $item) {
            $WebhookSubscriptions[] = $mapper->map($item);
        }

        return $WebhookSubscriptions;
    }
}
