<?php

namespace Piggy\Api\Resources\Register\Loyalty\Tokens;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\Receptions\CreditReceptionMapper;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\Resources\BaseResource;

class LoyaltyTokensResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/register/loyalty-tokens';

    /**
     * @throws PiggyRequestException
     */
    public function claim(string $version, string $uniqueId, int $timestamp, string $hash, string $contactUuid, ?int $credits = null, ?string $unitName = null, ?float $unitValue = null, ?int $shopId = null, ?string $shopUuid = null): CreditReception
    {
        $inputValues = [
            'version' => $version,
            'shop_id' => $shopId,
            'shop_uuid' => $shopUuid,
            'unique_id' => $uniqueId,
            'timestamp' => $timestamp,
            'hash' => $hash,
            'contact_uuid' => $contactUuid,
            'credits' => $credits,
            'unit_name' => $unitName,
            'unit_value' => $unitValue,
        ];

        $response = $this->client->post($this->resourceUri, $inputValues);

        $mapper = new CreditReceptionMapper();

        return $mapper->map($response->getData());
    }
}
