<?php

namespace Piggy\Api\Resources\OAuth\Brandkit;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Brandkit\BrandkitMapper;
use Piggy\Api\Models\Brandkit\Brandkit;
use Piggy\Api\Resources\BaseResource;

class BrandkitResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/brand-kit';

    /**
     * @throws PiggyRequestException
     */
    public function get(): Brandkit
    {
        $response = $this->client->get($this->resourceUri);

        $mapper = new BrandkitMapper();

        return $mapper->map($response->getData());
    }
}
