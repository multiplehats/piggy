<?php

namespace Piggy\Api\Models\Loyalty\Token;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\StaticMappers\Loyalty\Receptions\CreditReceptionMapper;

class LoyaltyToken
{
    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/loyalty-tokens';

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function create(array $body): string
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return $response->getData();
    }

    /**
     * @param  mixed[]  $body
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function claim(array $body): CreditReception
    {
        $response = ApiClient::post(self::resourceUri.'/claim', $body);

        return CreditReceptionMapper::map($response->getData());
    }
}
