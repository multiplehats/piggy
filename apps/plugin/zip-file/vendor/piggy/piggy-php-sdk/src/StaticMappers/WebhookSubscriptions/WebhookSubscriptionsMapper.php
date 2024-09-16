<?php

namespace Piggy\Api\StaticMappers\WebhookSubscriptions;

class WebhookSubscriptionsMapper
{
    public static function map($data): array
    {
        $webhookSubscriptions = [];

        foreach ($data as $item) {
            $webhookSubscriptions[] = WebhookSubscriptionMapper::map($item);
        }

        return $webhookSubscriptions;
    }
}
