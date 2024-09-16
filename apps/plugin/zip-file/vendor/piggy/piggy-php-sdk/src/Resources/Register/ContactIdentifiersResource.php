<?php

namespace Piggy\Api\Resources\Register;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\ContactIdentifiers\ContactIdentifierMapper;
use Piggy\Api\Models\Contacts\ContactIdentifier;
use Piggy\Api\Resources\BaseResource;

class ContactIdentifiersResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/register/contact-identifiers';

    /**
     * @throws PiggyRequestException
     */
    public function get(string $contactIdentifierValue): ContactIdentifier
    {
        $response = $this->client->get("$this->resourceUri/find", [
            'contact_identifier_value' => $contactIdentifierValue,
        ]);

        $mapper = new ContactIdentifierMapper();

        return $mapper->map($response->getData());
    }

    public function link(string $contactIdentifierValue, string $contactUuid): ContactIdentifier
    {
        $response = $this->client->put("$this->resourceUri/link", [
            'contact_identifier_value' => $contactIdentifierValue,
            'contact_uuid' => $contactUuid,
        ]);

        $mapper = new ContactIdentifierMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function unlink(string $contactIdentifierValue): ContactIdentifier
    {
        $response = $this->client->put("$this->resourceUri/unlink", [
            "contact_identifier_value" => $contactIdentifierValue,
        ]);

        $mapper = new ContactIdentifierMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function create(string $contactIdentifierValue, string $contactUuid, ?string $contactIdentifierName = ''): ContactIdentifier
    {
        $response = $this->client->post($this->resourceUri, [
            'contact_uuid' => $contactUuid,
            'contact_identifier_value' => $contactIdentifierValue,
            'contact_identifier_name' => $contactIdentifierName,
        ]);

        $mapper = new ContactIdentifierMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function delete(string $contactIdentifierValue): ContactIdentifier
    {
        $response = $this->client->destroy($this->resourceUri, [
            "contact_identifier_value" => $contactIdentifierValue,
        ]);

        $mapper = new ContactIdentifierMapper();

        return $mapper->map($response->getData());
    }
}
