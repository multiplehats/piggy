<?php

namespace Piggy\Api\Resources\Register\Loyalty\Program;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\LoyaltyProgramMapper;
use Piggy\Api\Models\Loyalty\LoyaltyProgram;
use Piggy\Api\Resources\BaseResource;

class LoyaltyProgramResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/register/loyalty-program';

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
