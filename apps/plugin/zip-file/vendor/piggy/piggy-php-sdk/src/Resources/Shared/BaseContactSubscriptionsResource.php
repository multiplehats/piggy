<?php

namespace Piggy\Api\Resources\Shared;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Contacts\SubscriptionMapper;
use Piggy\Api\Mappers\Contacts\SubscriptionsMapper;
use Piggy\Api\Models\Contacts\Subscription;
use Piggy\Api\Resources\BaseResource;

abstract class BaseContactSubscriptionsResource extends BaseResource
{
    /**
     * @return Subscription[]
     *
     * @throws PiggyRequestException
     */
    public function list(string $contactUuid): array
    {
        $response = $this->client->get("$this->resourceUri/$contactUuid", [
            'contact_uuid' => $contactUuid,
        ]);

        $mapper = new SubscriptionsMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function subscribe(string $contactUuid, string $subscriptionTypeUuid): Subscription
    {
        $response = $this->client->put("$this->resourceUri/$contactUuid/subscribe", [
            'subscription_type_uuid' => $subscriptionTypeUuid,
        ]);

        $mapper = new SubscriptionMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function unsubscribe(string $contactUuid, string $subscriptionTypeUuid): Subscription
    {
        $response = $this->client->put("$this->resourceUri/$contactUuid/unsubscribe", [
            'subscription_type_uuid' => $subscriptionTypeUuid,
        ]);

        $mapper = new SubscriptionMapper();

        return $mapper->map($response->getData());
    }
}
