<?php

namespace Piggy\Api\Resources\OAuth;

use Piggy\Api\Resources\Shared\BaseContactSubscriptionsResource;

class ContactSubscriptionsResource extends BaseContactSubscriptionsResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/contact-subscriptions';
}
