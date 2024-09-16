<?php

namespace Piggy\Api\Resources\OAuth\Loyalty\Tokens;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Loyalty\Receptions\CreditReceptionMapper;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\Resources\BaseResource;

class LoyaltyTokensResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/loyalty-tokens';

    /**
     * @throws PiggyRequestException
     */
    public function create(string $version, string $shopId, string $uniqueId, ?int $credits = null, ?string $unitName = null, ?float $unitValue = null): string
    {
        $inputValues = [
            'version' => $version,
            'shop_id' => $shopId,
            'unique_id' => $uniqueId,
            'credits' => $credits,
            'unit_name' => $unitName,
            'unit_value' => $unitValue,
        ];

        $response = $this->client->post($this->resourceUri, $inputValues);

        return $response->getData();
    }

    /**
     * @throws PiggyRequestException
     */
    public function claim(string $version, string $shopId, string $uniqueId, string $timeStamp, string $hash, string $contactUuid, ?int $credits = null, ?string $unitName = null, ?float $unitValue = null): CreditReception
    {
        $inputValues = [
            'version' => $version,
            'shop_id' => $shopId,
            'unique_id' => $uniqueId,
            'timestamp' => $timeStamp,
            'hash' => $hash,
            'contact_uuid' => $contactUuid,
            'credits' => $credits,
            'unit_name' => $unitName,
            'unit_value' => $unitValue,
        ];

        $response = $this->client->post($this->resourceUri.'/claim', $inputValues);

        $mapper = new CreditReceptionMapper();

        return $mapper->map($response->getData());
    }
}
