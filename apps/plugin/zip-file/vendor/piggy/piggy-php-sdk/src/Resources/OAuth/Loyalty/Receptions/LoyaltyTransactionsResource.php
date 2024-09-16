<?php

namespace Piggy\Api\Resources\OAuth\Loyalty\Receptions;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\LoyaltyTransactionMapper;
use Piggy\Api\Models\Loyalty\Receptions\BaseReception;
use Piggy\Api\Resources\BaseResource;

class LoyaltyTransactionsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/loyalty-transactions';

    /**
     * @return array<int, BaseReception|null>
     *
     * @throws PiggyRequestException
     */
    public function list(int $page = 1, ?string $contactUuid = null, ?string $type = null, ?string $shopUuid = null, int $limit = 10): array
    {
        $response = $this->client->get($this->resourceUri, [
            'limit' => $limit,
            'page' => $page,
            'contact_uuid' => $contactUuid,
            'type' => $type,
            'shop_uuid' => $shopUuid,
        ]);

        $mapper = new LoyaltyTransactionMapper();

        return $mapper->map($response->getData());
    }
}
