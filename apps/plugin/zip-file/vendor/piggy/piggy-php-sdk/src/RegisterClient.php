<?php

namespace Piggy\Api;

use Piggy\Api\Http\BaseClient;
use Piggy\Api\Http\Traits\SetsRegisterResources as RegisterResources;

class RegisterClient extends BaseClient
{
    use RegisterResources;

    /** @var string */
    public $apiKey;

    /**
     * RegisterClient constructor.
     */
    public function __construct(string $apiKey)
    {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->setApiKey($apiKey);
        $this->setResources($this);
    }

    public function setApiKey(string $apiKey): self
    {
        $this->addHeader('Authorization', "Bearer $apiKey");

        return $this;
    }
}
