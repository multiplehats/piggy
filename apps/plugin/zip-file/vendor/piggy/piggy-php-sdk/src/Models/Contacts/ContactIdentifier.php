<?php

namespace Piggy\Api\Models\Contacts;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\ContactIdentifiers\ContactIdentifierMapper;

class ContactIdentifier
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var Contact|null
     */
    protected $contact;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/contact-identifiers';

    public function __construct(string $value, bool $active, ?string $name = '', ?Contact $contact = null)
    {
        $this->value = $value;
        $this->name = $name;
        $this->active = $active;
        $this->contact = $contact;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(array $params = []): ContactIdentifier
    {
        $response = ApiClient::get(self::resourceUri.'/find', $params);

        return ContactIdentifierMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): ContactIdentifier
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return ContactIdentifierMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException|PiggyRequestException
     */
    public static function link(array $params): ContactIdentifier
    {
        $response = ApiClient::put(self::resourceUri.'/link', $params);

        return ContactIdentifierMapper::map($response->getData());
    }

    /**
     * @param array<string, string> $params
     * @return ContactIdentifier
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException|PiggyRequestException
     */
    public static function unlink(array $params): ContactIdentifier
    {
        $response = ApiClient::put(self::resourceUri . "/unlink", $params);

        return ContactIdentifierMapper::map($response->getData());

    }

    /**
     * @param array<string, string> $params
     * @return ContactIdentifier
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException|PiggyRequestException
     */
    public static function delete(array $params): ContactIdentifier
    {
        $response = ApiClient::delete(self::resourceUri, $params);

        return ContactIdentifierMapper::map($response->getData());
    }
}
