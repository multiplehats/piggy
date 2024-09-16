<?php

namespace Piggy\Api\Resources\OAuth\Loyalty\Rewards;

use Exception;
use Piggy\Api\Enum\CustomAttributeTypes;
use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\RewardAttributes\RewardAttributeMapper;
use Piggy\Api\Mappers\Loyalty\RewardAttributes\RewardAttributesMapper;
use Piggy\Api\Models\Loyalty\RewardAttributes\RewardAttribute;
use Piggy\Api\Resources\BaseResource;

class RewardAttributesResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/reward-attributes';

    /**
     * @return RewardAttribute[]
     *
     * @throws PiggyRequestException
     */
    public function list(int $page = 1, int $limit = 30): array
    {
        $response = $this->client->get($this->resourceUri, [
            'page' => $page,
            'limit' => $limit,
        ]);

        $mapper = new RewardAttributesMapper();

        return $mapper->map((array) $response->getData());
    }

    /**
     * @param  mixed[]|null  $options
     *
     * @throws PiggyRequestException
     */
    public function create(string $name, string $label, ?string $description, string $dataType, ?array $options = null, ?string $placeholder = null): RewardAttribute
    {
        $rewardAttributes = [
            'name' => $name,
            'label' => $label,
            'description' => $description,
            'type' => $dataType,
            'options' => $options,
            'placeholder' => $placeholder,
        ];

        if (! CustomAttributeTypes::has($dataType)) {
            throw new Exception("DataType {$dataType} invalid");
        }

        $response = $this->client->post($this->resourceUri, $rewardAttributes);

        $mapper = new RewardAttributeMapper();

        return $mapper->map($response->getData());
    }
}
