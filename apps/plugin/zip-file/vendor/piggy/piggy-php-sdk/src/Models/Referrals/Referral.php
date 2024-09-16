<?php

namespace Piggy\Api\Models\Referrals;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\Referrals\ReferralsMapper;

class Referral
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var object
     */
    protected $referring_contact;

    /**
     * @var object
     */
    protected $referred_contact;

    /**
     * TODO: Refactor to enum
     *
     * @var string
     */
    protected $status;

    const resourceUri = '/api/v3/oauth/clients/referrals';

    public function __construct(string $uuid, object $referredContact, object $referringContact, string $status)
    {
        $this->uuid = $uuid;
        $this->referring_contact = $referringContact;
        $this->referred_contact = $referredContact;
        $this->status = $status;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getReferringContact(): object
    {
        return $this->referring_contact;
    }

    public function getReferredContact(): object
    {
        return $this->referred_contact;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param  mixed[]  $params
     * @return Referral[]
     *
     * @throws GuzzleException
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     */
    public static function list(array $params = []): array
    {
        $response = ApiClient::get(self::resourceUri, $params);

        return ReferralsMapper::map($response->getData());
    }
}
