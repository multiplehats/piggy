<?php

namespace Piggy\Api\Models;

class OAuthToken
{
    /**
     * @var string
     */
    protected $access_token;

    /**
     * @var string
     */
    protected $refresh_token;

    /**
     * @var int
     */
    protected $expires_in;

    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    public function setAccessToken(string $access_token): void
    {
        $this->access_token = $access_token;
    }

    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    public function setRefreshToken(string $refresh_token): void
    {
        $this->refresh_token = $refresh_token;
    }

    public function getExpiresIn(): int
    {
        return $this->expires_in;
    }

    public function setExpiresIn(int $expires_in): void
    {
        $this->expires_in = $expires_in;
    }
}
