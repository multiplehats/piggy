<?php

namespace Piggy\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Http\BaseClient;
use Piggy\Api\Http\Traits\SetsOAuthResources as OAuthResources;

class OAuthClient extends BaseClient
{
    use OAuthResources;

    /**
     * @var int
     */
    public $clientId;

    /**
     * @var string
     */
    public $clientSecret;

    /**
     * OAuthClient constructor.
     */
    public function __construct(int $clientId, string $clientSecret, ?ClientInterface $client = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        parent::__construct($client);

    }

    /**
     * @return Http\Responses\Response
     *
     * @throws Exceptions\PiggyRequestException
     */
    public function ping()
    {
        return $this->get('/api/v2/oauth/clients');
    }

    /**
     * @throws MaintenanceModeException
     * @throws PiggyRequestException
     * @throws GuzzleException
     */
    public function getAccessToken(): string
    {
        $response = $this->authenticationRequest('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        return $response->getAccessToken();
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->addHeader('Authorization', "Bearer $accessToken");

        return $this;
    }
}
