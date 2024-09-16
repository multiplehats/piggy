<?php

namespace Piggy\Api\Resources\OAuth\Contacts;

use Exception;
use Piggy\Api\Enum\CustomAttributeTypes;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Contacts\AttributeMapper;
use Piggy\Api\Mappers\Contacts\AttributesMapper;
use Piggy\Api\Models\Contacts\Attribute;
use Piggy\Api\Resources\BaseResource;

class ContactAttributesResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/contact-attributes';

    /**
     * @param  mixed[]  $params
     * @return Attribute[]
     *
     * @throws PiggyRequestException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get($this->resourceUri, $params);

        $mapper = new AttributesMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param  mixed[]|null  $options
     *
     * @throws PiggyRequestException
     * @throws Exception
     */
    public function create(string $name, string $label, string $type, ?string $description = null, ?array $options = null): Attribute
    {
        $contactAttributes = [
            'name' => $name,
            'label' => $label,
            'data_type' => $type,
            'description' => $description,
            'options' => $options,
        ];

        if (! CustomAttributeTypes::has($type)) {
            throw new Exception("type {$type} invalid");
        }

        $response = $this->client->post($this->resourceUri, $contactAttributes);

        $mapper = new AttributeMapper();

        return $mapper->map($response->getData());
    }
}
