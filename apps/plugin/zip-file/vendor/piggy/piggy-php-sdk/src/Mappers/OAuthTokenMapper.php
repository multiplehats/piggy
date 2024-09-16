<?php

namespace Piggy\Api\Mappers;

use Piggy\Api\Http\Responses\AuthenticationResponse;
use Piggy\Api\Models\OAuthToken;

class OAuthTokenMapper
{
    public function map(AuthenticationResponse $data): OAuthToken
    {
        $token = new OAuthToken();

        $token->setAccessToken($data->getAccessToken());
        $token->setExpiresIn($data->getExpiresIn());

        return $token;
    }
}
