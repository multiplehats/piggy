<?php

namespace Piggy\Api\StaticMappers;

use Piggy\Api\Models\OAuthToken;

class OAuthTokenMapper
{
    public static function map(object $data): OAuthToken
    {
        $token = new OAuthToken();

        $token->setAccessToken($data->getAccessToken());
        $token->setExpiresIn($data->getExpiresIn());

        return $token;
    }
}
