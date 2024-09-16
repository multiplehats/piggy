<?php

namespace Piggy\Api\Resources\OAuth\Units;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Units\UnitMapper;
use Piggy\Api\Mappers\Units\UnitsMapper;
use Piggy\Api\Models\Loyalty\Unit;
use Piggy\Api\Resources\BaseResource;

class UnitsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/units';

    /**
     * @param  mixed[]  $params
     * @return Unit[]
     *
     * @throws PiggyRequestException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get("$this->resourceUri", $params);

        $mapper = new UnitsMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function create(string $name, string $label, ?bool $isDefault = false): Unit
    {
        $response = $this->client->post($this->resourceUri, [
            'name' => $name,
            'label' => $label,
            'is_default' => $isDefault,
        ]);

        $mapper = new UnitMapper();

        return $mapper->map($response->getData());
    }
}
