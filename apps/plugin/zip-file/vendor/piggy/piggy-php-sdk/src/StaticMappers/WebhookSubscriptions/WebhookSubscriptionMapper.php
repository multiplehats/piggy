<?php

namespace Piggy\Api\StaticMappers\WebhookSubscriptions;

use Piggy\Api\Models\WebhookSubscriptions\WebhookSubscription;
use Piggy\Api\StaticMappers\BaseMapper;

class WebhookSubscriptionMapper extends BaseMapper
{
    public static function map($data): WebhookSubscription
    {
        return new WebhookSubscription(
            $data->uuid,
            $data->name,
            $data->event_type,
            $data->url,
            $data->properties ?? [],
            $data->status,
            $data->version,
            self::parseDate($data->created_at)
        );
    }
}
