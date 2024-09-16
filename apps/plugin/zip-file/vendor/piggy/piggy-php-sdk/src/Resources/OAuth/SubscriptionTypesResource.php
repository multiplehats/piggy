<?php

namespace Piggy\Api\Resources\OAuth;

use Piggy\Api\Resources\Shared\BaseSubscriptionTypesResource;

class SubscriptionTypesResource extends BaseSubscriptionTypesResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/subscription-types';
}
