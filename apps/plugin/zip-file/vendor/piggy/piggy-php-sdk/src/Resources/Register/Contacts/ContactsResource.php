<?php

namespace Piggy\Api\Resources\Register\Contacts;

use Exception;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Contacts\ContactMapper;
use Piggy\Api\Mappers\Loyalty\CreditBalanceMapper;
use Piggy\Api\Mappers\Loyalty\LoyaltyTransactionMapper;
use Piggy\Api\Mappers\Prepaid\PrepaidBalanceMapper;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\Loyalty\CreditBalance;
use Piggy\Api\Models\Loyalty\Receptions\BaseReception;
use Piggy\Api\Models\Prepaid\PrepaidBalance;
use Piggy\Api\Resources\BaseResource;

class ContactsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/register/contacts';

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
     * @param  mixed[]  $attributes
     *
     * @throws PiggyRequestException
     */
    public function update(string $contactUuid, array $attributes): Contact
    {
        $response = $this->client->put("$this->resourceUri/$contactUuid", [
            'attributes' => $attributes,
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
     * @return array<int, BaseReception|null>
     *
     * @throws PiggyRequestException
     * @throws Exception
     */
    public function getTransactions(string $contactUuid, int $page = 1, ?string $shopUuid = null, ?string $type = null, int $limit = 30): ?array
    {
        $response = $this->client->get("$this->resourceUri/$contactUuid/loyalty-transactions", [
            'limit' => $limit,
            'page' => $page,
            'shop_uuid' => $shopUuid,
            'type' => $type,
        ]);

        $mapper = new LoyaltyTransactionMapper();

        return $mapper->map($response->getData());
    }
}
