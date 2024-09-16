<?php

namespace Piggy\Api\Resources\Shared;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Contacts\SubscriptionTypesMapper;
use Piggy\Api\Models\Contacts\SubscriptionType;
use Piggy\Api\Resources\BaseResource;

abstract class BaseSubscriptionTypesResource extends BaseResource
{
    /**
     * @return SubscriptionType[]
     *
     * @throws PiggyRequestException
     */
    public function list(): array
    {
        $response = $this->client->get($this->resourceUri);

        $mapper = new SubscriptionTypesMapper();

        return $mapper->map($response->getData());
    }
}
