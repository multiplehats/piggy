<?php

namespace Piggy\Api\Models\Loyalty\Receptions;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Loyalty\Receptions\CreditReceptionMapper;
use Piggy\Api\StaticMappers\Loyalty\Receptions\RewardReceptionMapper;

class RewardReception extends BaseReception
{
    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/reward-receptions';

    /**
     * @param  mixed[]  $body
     * @return DigitalRewardReception|PhysicalRewardReception|null
     *
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function create(array $body)
    {
        $response = ApiClient::post(self::resourceUri, $body);

        return RewardReceptionMapper::map($response->getData());
    }

    /**
     * @param string $rewardReceptionUuid
     * @return CreditReception
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function reverse(string $rewardReceptionUuid)
    {
        $response = ApiClient::post(self::resourceUri . "/$rewardReceptionUuid/reverse", []);

        return CreditReceptionMapper::map($response->getData());
    }

}