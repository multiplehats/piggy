<?php

namespace Piggy\Api\Resources\OAuth\Contacts;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Contacts\ContactMapper;
use Piggy\Api\Mappers\Contacts\ContactsMapper;
use Piggy\Api\Mappers\Loyalty\CreditBalanceMapper;
use Piggy\Api\Mappers\Prepaid\PrepaidBalanceMapper;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\Loyalty\CreditBalance;
use Piggy\Api\Models\Prepaid\PrepaidBalance;
use Piggy\Api\Resources\BaseResource;

class ContactsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/contacts';

    /**
     * @throws PiggyRequestException
     */
    public function get(string $contactUuid): Contact
    {
        $response = $this->client->get("$this->resourceUri/$contactUuid");

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());

    }

    /**
     * @throws PiggyRequestException
     */
    public function findOneBy(string $email): Contact
    {
        $response = $this->client->get("$this->resourceUri/find-one-by", [
            'email' => $email,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function findOrCreate(string $email): Contact
    {
        $response = $this->client->get("$this->resourceUri/find-or-create", [
            'email' => $email,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function create(string $email): Contact
    {
        $response = $this->client->post("$this->resourceUri", [
            'email' => $email,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @return Contact[]
     *
     * @throws PiggyRequestException
     */
    public function list(?int $page = 1, ?int $limit = 30): array
    {
        $response = $this->client->get("$this->resourceUri", [
            'page' => $page,
            'limit' => $limit,
        ]);

        $mapper = new ContactsMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function createAnonymously(?string $contactIdentifierValue = null): Contact
    {
        $response = $this->client->post("$this->resourceUri/anonymous", [
            'contact_identifier_value' => $contactIdentifierValue,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param  mixed[]  $contactAttributes
     *
     * @throws PiggyRequestException
     */
    public function update(string $contactUuid, array $contactAttributes): Contact
    {
        $response = $this->client->put("$this->resourceUri/$contactUuid", [
            'attributes' => $contactAttributes,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function getPrepaidBalance(string $contactUuid): PrepaidBalance
    {
        $response = $this->client->get("$this->resourceUri/$contactUuid/prepaid-balance");

        $mapper = new PrepaidBalanceMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function getCreditBalance(string $contactUuid): CreditBalance
    {
        $response = $this->client->get("$this->resourceUri/$contactUuid/credit-balance");

        $mapper = new CreditBalanceMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function createAsync(string $email): Contact
    {
        $response = $this->client->post("$this->resourceUri/async", [
            'email' => $email,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function findOrCreateAsync(string $email): Contact
    {
        $response = $this->client->get("$this->resourceUri/find-or-create/async", [
            'email' => $email,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @return null
     *
     * @throws PiggyRequestException
     */
    public function destroy(string $contactUuid, string $type)
    {
        $response = $this->client->post("$this->resourceUri/$contactUuid/delete", [
            'type' => $type, // TODO: Refactor to enum
        ]);

        return $response->getData();
    }

    /**
     * @throws PiggyRequestException
     */
    public function claimAnonymousContact(string $contactUuid, string $email): Contact
    {
        $response = $this->client->put("$this->resourceUri/$contactUuid/claim", [
            'email' => $email,
        ]);

        $mapper = new ContactMapper();

        return $mapper->map($response->getData());
    }
}
