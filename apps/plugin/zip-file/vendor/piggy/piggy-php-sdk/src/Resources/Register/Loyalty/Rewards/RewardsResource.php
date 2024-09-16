<?php

namespace Piggy\Api\Resources\Register\Loyalty\Rewards;

use Exception;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\Rewards\RewardMapper;
use Piggy\Api\Mappers\Loyalty\Rewards\RewardsMapper;
use Piggy\Api\Models\Loyalty\Rewards\Reward;
use Piggy\Api\Resources\BaseResource;

class RewardsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/register/rewards';

    /**
     * @return Reward[]
     *
     * @throws PiggyRequestException
     */
    public function list(): array
    {
        $response = $this->client->get($this->resourceUri);

        $mapper = new RewardsMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @return array<Reward>
     *
     * @throws PiggyRequestException
     * @throws Exception
     */
    public function get(string $contactUuid): array
    {
        $response = $this->client->get($this->resourceUri, [
            'contact_uuid' => $contactUuid,
        ]);
        $mapper = new RewardsMapper();

        return $mapper->map($response->getData());
    }

    public function findBy(string $rewardUuid): Reward
    {
        $response = $this->client->get("$this->resourceUri/$rewardUuid");

        $mapper = new RewardMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function update(Reward $reward): Reward
    {
        $data = array_merge([
            'title' => $reward->getTitle(),
            'description' => $reward->getDescription(),
            'required_credits' => $reward->getRequiredCredits(),
        ], $reward->getAttributes());

        $response = $this->client->put("$this->resourceUri/{$reward->getUuid()}", $data);
        $mapper = new RewardMapper();

        return $mapper->map($response->getData());
    }
}
