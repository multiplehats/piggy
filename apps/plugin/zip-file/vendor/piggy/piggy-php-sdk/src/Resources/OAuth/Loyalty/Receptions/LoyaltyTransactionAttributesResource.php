<?php

namespace Piggy\Api\Resources\OAuth\Loyalty\Receptions;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\LoyaltyTransactionAttributes\LoyaltyTransactionAttributeMapper;
use Piggy\Api\Mappers\Loyalty\LoyaltyTransactionAttributes\LoyaltyTransactionAttributesMapper;
use Piggy\Api\Models\Loyalty\Transactions\LoyaltyTransactionAttribute;
use Piggy\Api\Resources\BaseResource;

class LoyaltyTransactionAttributesResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/loyalty-transaction-attributes';

    /**
     * @param  mixed[]  $params
     * @return LoyaltyTransactionAttribute[]
     *
     * @throws PiggyRequestException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get($this->resourceUri, $params);

        $mapper = new LoyaltyTransactionAttributesMapper();

        return $mapper->map((array) $response->getData());
    }

    /**
     * @param  mixed[]|null  $options
     *
     * @throws PiggyRequestException
     */
    public function create(string $name, string $dataType, ?string $label = null, ?string $description = null, ?array $options = null): LoyaltyTransactionAttribute
    {
        $loyaltyTransactionAttributes = [
            'name' => $name,
            'data_type' => $dataType,
            'label' => $label,
            'description' => $description,
            'options' => $options,
        ];

        $response = $this->client->post($this->resourceUri, $loyaltyTransactionAttributes);

        $mapper = new LoyaltyTransactionAttributeMapper();

        return $mapper->map($response->getData());
    }
}
