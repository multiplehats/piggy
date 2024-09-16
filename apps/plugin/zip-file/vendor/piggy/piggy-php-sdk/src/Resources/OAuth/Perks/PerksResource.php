<?php

namespace Piggy\Api\Resources\OAuth\Perks;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Models\Perks\Perk;
use Piggy\Api\Resources\BaseResource;
use Piggy\Api\Mappers\Perks\PerkMapper;
use Piggy\Api\Mappers\Perks\PerksMapper;

class PerksResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/perks';

    /**
     * @param  mixed[]  $params
     * @return Perk[]
     *
     * @throws PiggyRequestException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get($this->resourceUri, $params);

        $mapper = new PerksMapper();

        return $mapper->map((array) $response->getData());
    }

    /**
     * @param mixed[] $options
     *
     * @throws PiggyRequestException
     */
    public function create(string $label, string $name, string $dataType, array $options): Perk
    {
        $response = $this->client->post($this->resourceUri, [
            'label' => $label,
            'name' => $name,
            'dataType' => $dataType,
            'options' => $options,
        ]);

        $mapper = new PerkMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws PiggyRequestException
     */
    public function get(string $perkUuid, array $params = []): Perk
    {
        $response = $this->client->get("$this->resourceUri/$perkUuid", $params);

        $mapper = new PerkMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function update(string $perkUuid, string $label): Perk
    {
        $response = $this->client->put("$this->resourceUri/$perkUuid", [
            'label' => $label,
        ]);

        $mapper = new PerkMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param mixed[] $params
     * @return null
     *
     * @throws PiggyRequestException
     */
    public function delete(string $perkUuid, array $params = [])
    {
        $response = $this->client->destroy("$this->resourceUri/$perkUuid", $params);

        return $response->getData();
    }
}
