<?php

namespace Piggy\Api;

use Piggy\Api\Http\BaseClient;
use Piggy\Api\Http\Traits\SetsOAuthResources;

class OAuthClientApiKey extends BaseClient
{
    use SetsOAuthResources;

    /**
     * OAuthClient constructor.
     */
    public function __construct(string $apiKey)
    {
        parent::__construct();

        $this->setApiKey($apiKey);
    }

    /**
     * @return $this
     */
    public function setApiKey(string $apiKey): self
    {
        $this->addHeader('Authorization', "Bearer $apiKey");

        return $this;
    }
}
