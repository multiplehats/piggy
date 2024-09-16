<?php

namespace Piggy\Api\Resources\OAuth\Giftcards\Program;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Giftcards\GiftcardProgramsMapper;
use Piggy\Api\Models\Giftcards\GiftcardProgram;
use Piggy\Api\Resources\BaseResource;

class GiftcardProgramsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/giftcard-programs';

    /**
     * @return GiftcardProgram[]
     *
     * @throws PiggyRequestException
     */
    public function list(): array
    {
        $response = $this->client->get($this->resourceUri);

        $mapper = new GiftcardProgramsMapper();

        return $mapper->map($response->getData());
    }
}
