<?php

namespace Piggy\Api\Resources;

use Piggy\Api\Http\BaseClient;

abstract class BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri;

    /**
     * @var BaseClient
     */
    protected $client;

    /**
     * BaseResource constructor.
     */
    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }
}
