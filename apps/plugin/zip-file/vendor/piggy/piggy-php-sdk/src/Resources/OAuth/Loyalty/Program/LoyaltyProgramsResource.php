<?php

namespace Piggy\Api\Resources\OAuth\Loyalty\Program;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\LoyaltyProgramMapper;
use Piggy\Api\Models\Loyalty\LoyaltyProgram;
use Piggy\Api\Resources\BaseResource;

class LoyaltyProgramsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/loyalty-program';

    /**
     * @throws PiggyRequestException
     */
    public function get(): LoyaltyProgram
    {
        $response = $this->client->get($this->resourceUri);

        $mapper = new LoyaltyProgramMapper();

        return $mapper->map($response->getData());
    }
}
