<?php

namespace Piggy\Api\Resources\OAuth;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\CustomAttributes\CustomAttributeMapper;
use Piggy\Api\Mappers\CustomAttributes\CustomAttributesMapper;
use Piggy\Api\Models\CustomAttributes\CustomAttribute;
use Piggy\Api\Resources\BaseResource;

class CustomAttributeResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/custom-attributes';

    /**
     * @param  mixed[]  $params
     * @return CustomAttribute[]
     *
     * @throws PiggyRequestException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get($this->resourceUri, $params);

        $mapper = new CustomAttributesMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param  mixed[]  $options
     * @throws PiggyRequestException
     */
    public function create(string $entity, string $name, string $label, string $type, ?array $options = null, ?string $description = null, ?string $groupName = null): CustomAttribute
    {
        $response = $this->client->post("$this->resourceUri", [
            'entity' => $entity,
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'options' => $options,
            'description' => $description,
            'group_name' => $groupName,
        ]);

        $mapper = new CustomAttributeMapper();

        return $mapper->map($response->getData());
    }
}
