<?php

namespace Piggy\Api\Models\ContactsPortal;

use GuzzleHttp\Exception\GuzzleException;
use Piggy\Api\ApiClient;
use Piggy\Api\Exceptions\MaintenanceModeException;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\StaticMappers\ContactsPortal\ContactsPortalMapper;

class ContactsPortal
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    const resourceUri = '/api/v3/oauth/clients/contacts-portal';

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param  mixed[]  $params
     *
     * @throws MaintenanceModeException|GuzzleException|PiggyRequestException
     */
    public static function getAuthUrl(array $params): ContactsPortal
    {
        $response = ApiClient::get(self::resourceUri.'/auth-url', $params);

        return ContactsPortalMapper::map($response->getData());
    }
}
