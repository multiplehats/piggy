<?php

namespace Piggy\Api\Models\Loyalty;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Loyalty\CreditBalanceMapper;

class CreditBalance
{
    /**
     * @var int
     */
    protected $balance;

    /**
     * @var string
     */
    const contactsResourceUri = '/api/v3/oauth/clients/contacts';

    public function __construct(int $balance)
    {
        $this->balance = $balance;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function findBy(string $contactUuid, array $params = []): CreditBalance
    {
        $response = ApiClient::get(self::contactsResourceUri."/$contactUuid/credit-balance", $params);

        return CreditBalanceMapper::map($response->getData());
    }
}
