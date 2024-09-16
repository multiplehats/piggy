<?php

namespace Piggy\Api\Resources\OAuth\Tiers;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Tiers\TierMapper;
use Piggy\Api\Mappers\Tiers\TiersMapper;
use Piggy\Api\Models\Tiers\Tier;
use Piggy\Api\Resources\BaseResource;

class TiersResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/tiers';

    /**
     * @param  mixed[]  $params
     * @return Tier[]
     *
     * @throws PiggyRequestException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get($this->resourceUri, $params);

        $mapper = new TiersMapper();

        return $mapper->map((array) $response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function getTierForContact(string $contactUuid): Tier
    {
        $resourceUri = '/api/v3/oauth/clients/contacts';

        $response = $this->client->get("$resourceUri/$contactUuid/tier");

        $mapper = new TierMapper();

        return $mapper->map($response->getData());
    }
}
