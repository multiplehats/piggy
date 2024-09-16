<?php

namespace Piggy\Api\Resources\OAuth\Loyalty\Rewards;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\Rewards\CollectableRewardMapper;
use Piggy\Api\Mappers\Loyalty\Rewards\CollectableRewardsMapper;
use Piggy\Api\Models\Loyalty\Rewards\CollectableReward;
use Piggy\Api\Resources\BaseResource;

class CollectableRewardsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/collectable-rewards';

    /**
     * @return CollectableReward[]
     *
     * @throws PiggyRequestException
     */
    public function list(string $contactUuid): array
    {
        $response = $this->client->get($this->resourceUri, [
            'contact_uuid' => $contactUuid,
        ]);

        $mapper = new CollectableRewardsMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function collect(string $loyaltyTransactionUuid): CollectableReward
    {
        $response = $this->client->put("$this->resourceUri/collect/{$loyaltyTransactionUuid}", []);

        $mapper = new CollectableRewardMapper();

        return $mapper->map($response->getData());
    }
}
