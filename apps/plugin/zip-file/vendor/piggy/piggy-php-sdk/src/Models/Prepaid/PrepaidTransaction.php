<?php

namespace Piggy\Api\Models\Prepaid;

use DateTime;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Prepaid\PrepaidTransactionMapper;

class PrepaidTransaction
{
    /**
     * @var int
     */
    protected $amountInCents;

    /**
     * @var PrepaidBalance
     */
    protected $prepaidBalance;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/prepaid-transactions';

    public function __construct(int $amountInCents, PrepaidBalance $prepaidBalance, string $uuid, DateTime $createdAt)
    {
        $this->amountInCents = $amountInCents;
        $this->prepaidBalance = $prepaidBalance;
        $this->uuid = $uuid;
        $this->createdAt = $createdAt;
    }

    public function getAmountInCents(): int
    {
        return $this->amountInCents;
    }

    public function getPrepaidBalance(): PrepaidBalance
    {
        return $this->prepaidBalance;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): PrepaidTransaction
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return PrepaidTransactionMapper::map($response->getData());
    }
}
