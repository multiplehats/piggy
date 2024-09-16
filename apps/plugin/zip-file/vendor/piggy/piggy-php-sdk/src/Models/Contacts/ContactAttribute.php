<?php

namespace Piggy\Api\Models\Contacts;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Contacts\AttributeMapper;
use Piggy\Api\StaticMappers\Contacts\AttributesMapper;

class ContactAttribute
{
    /** @var string */
    protected $value;

    /** @var ?Attribute */
    protected $attribute;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/contact-attributes';

    public function __construct(string $value, ?Attribute $attribute)
    {
        $this->value = $value;
        $this->attribute = $attribute;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    public function setAttribute(Attribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    /**
     * @param  mixed[]  $params
     * @return Attribute[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return AttributesMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): Attribute
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return AttributeMapper::map($response->getData());
    }
}
