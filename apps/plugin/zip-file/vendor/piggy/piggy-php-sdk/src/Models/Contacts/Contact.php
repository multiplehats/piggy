<?php

namespace Piggy\Api\Models\Contacts;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Http\Responses\Response;
use Piggy\Api\Models\Loyalty\CreditBalance;
use Piggy\Api\Models\Prepaid\PrepaidBalance;
use Piggy\Api\StaticMappers\Contacts\ContactMapper;
use Piggy\Api\StaticMappers\Contacts\ContactsMapper;
use stdClass;

class Contact
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string|null
     */
    protected $email;

    /**
     * @var PrepaidBalance|null
     */
    protected $prepaidBalance;

    /**
     * @var CreditBalance|null
     */
    protected $creditBalance;

    /**
     * @var ?Subscription[]
     */
    protected $subscriptions;

    /**
     * @var ContactAttribute[]|null
     */
    protected $attributes;

    /**
     * @var mixed[]|null
     */
    protected $currentValues;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/contacts';

    /**
     * @param  ContactAttribute[]|null  $attributes
     * @param  Subscription[]|null  $subscriptions
     * @param  mixed[]|null  $currentValues
     */
    public function __construct(string $uuid, ?string $email, ?PrepaidBalance $prepaidBalance, ?CreditBalance $creditBalance, ?array $attributes, ?array $subscriptions = null, ?array $currentValues = null)
    {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->prepaidBalance = $prepaidBalance;
        $this->creditBalance = $creditBalance;
        $this->subscriptions = $subscriptions;
        $this->attributes = $attributes;
        $this->currentValues = $currentValues;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPrepaidBalance(): ?PrepaidBalance
    {
        return $this->prepaidBalance;
    }

    public function getCreditBalance(): ?CreditBalance
    {
        return $this->creditBalance;
    }

    public function setCreditBalance(?CreditBalance $creditBalance): void
    {
        $this->creditBalance = $creditBalance;
    }

    /**
     * @return ContactAttribute[]|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * @return ?Subscription[]
     */
    public function getSubscriptions(): ?array
    {
        return $this->subscriptions;
    }

    /**
     * @return mixed[]|null
     */
    public function getCurrentValues(): ?array
    {
        return $this->currentValues;
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function get(string $contactUuid, array $params = []): Contact
    {
        $response = ApiClient::get(self::resourceUri."/$contactUuid", $params);

        return ContactMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function findOrCreate(array $params): Contact
    {
        $response = ApiClient::get(self::resourceUri.'/find-or-create', $params);

        return ContactMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function findOneBy(array $params): Contact
    {
        $response = ApiClient::get(self::resourceUri.'/find-one-by', $params);

        return ContactMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     * @return Contact[]
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return ContactsMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function findOrCreateAsync(array $params): stdClass
    {
        $response = ApiClient::get(self::resourceUri.'/find-or-create/async', $params);

        return $response->getData();
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): stdClass
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return $response->getData();
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function createAnonymously(array $body = []): stdClass
    {
        $response = ApiClient::post(self::resourceUri.'/anonymous', $body);

        return $response->getData();
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function update(string $contactUuid, array $body): Contact
    {
        $response = ApiClient::put(self::resourceUri."/$contactUuid", $body);

        return ContactMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function createAsync(array $body): stdClass
    {
        $response = ApiClient::post(self::resourceUri.'/async', $body);

        return $response->getData();
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function delete(string $contactUuid, array $body = []): Response
    {
        return ApiClient::post(self::resourceUri."/$contactUuid/delete", $body);
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function claimAnonymousContact(string $contactUuid, array $body = []): Contact
    {
        $response = ApiClient::put(self::resourceUri."/$contactUuid/claim", $body);

        return ContactMapper::map($response->getData());
    }
}
