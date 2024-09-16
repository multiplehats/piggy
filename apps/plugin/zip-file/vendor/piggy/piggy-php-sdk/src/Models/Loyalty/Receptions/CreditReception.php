<?php

namespace Piggy\Api\Models\Loyalty\Receptions;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\Models\Contacts\ContactIdentifier;
use Piggy\Api\Models\Loyalty\Unit;
use Piggy\Api\Models\Shops\Shop;
use Piggy\Api\StaticMappers\Loyalty\Receptions\CreditReceptionMapper;
use stdClass;

class CreditReception extends BaseReception
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $credits;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var ?Contact
     */
    private $contact;

    /**
     * @var ?Shop
     */
    private $shop;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var ContactIdentifier|null
     */
    private $contactIdentifier;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /** @var ?float */
    protected $unitValue;

    /** @var ?Unit */
    protected $unit;

    /** @var mixed[] */
    protected $attributes;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/credit-receptions';

    /**
     * @param  mixed[]  $attributes
     */
    public function __construct(string $type, int $credits, string $uuid, ?Contact $contact, ?Shop $shop, string $channel, ?ContactIdentifier $contactIdentifier, DateTime $createdAt, ?float $unitValue = null, ?Unit $unit = null, array $attributes = [])
    {
        $this->type = $type;
        $this->credits = $credits;
        $this->uuid = $uuid;
        $this->contact = $contact;
        $this->shop = $shop;
        $this->channel = $channel;
        $this->contactIdentifier = $contactIdentifier;
        $this->createdAt = $createdAt;
        $this->unitValue = $unitValue;
        $this->unit = $unit;
        $this->attributes = $attributes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getContactIdentifier(): ?ContactIdentifier
    {
        return $this->contactIdentifier;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUnitValue(): ?float
    {
        return $this->unitValue;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    /**
     * @return mixed[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): CreditReception
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return CreditReceptionMapper::map($response->getData());
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function calculate(array $params): stdClass
    {
        $response = ApiClient::get(self::resourceUri.'/calculate', $params);

        return $response->getData();
    }

    /**
     * @param string $creditReceptionUuid
     * @return CreditReception
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function reverse(string $creditReceptionUuid): CreditReception
    {
        $response = ApiClient::post(self::resourceUri . "/$creditReceptionUuid/reverse", []);

        return CreditReceptionMapper::map($response->getData());
    }
}
