<?php

namespace Piggy\Api\Resources\OAuth\Shops;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Shops\ShopMapper;
use Piggy\Api\Mappers\Shops\ShopsMapper;
use Piggy\Api\Models\Shops\Shop;
use Piggy\Api\Resources\BaseResource;

class ShopsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/shops';

    /**
     * @param  mixed[]  $params
     * @return Shop[]
     *
     * @throws PiggyRequestException
     */
    public function all(array $params = []): array
    {
        $response = $this->client->get($this->resourceUri, $params);

        $mapper = new ShopsMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws PiggyRequestException
     */
    public function get(string $shopUuid, array $params = []): Shop
    {
        $response = $this->client->get("$this->resourceUri/$shopUuid", $params);

        $mapper = new ShopMapper();

        return $mapper->map($response->getData());
    }
}
