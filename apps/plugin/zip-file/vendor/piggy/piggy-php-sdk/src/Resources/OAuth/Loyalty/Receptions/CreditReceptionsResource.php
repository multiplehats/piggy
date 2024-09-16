<?php

namespace Piggy\Api\Resources\OAuth\Loyalty\Receptions;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Giftcards\GiftcardTransactionMapper;
use Piggy\Api\Mappers\Loyalty\Receptions\CreditReceptionMapper;
use Piggy\Api\Models\Giftcards\GiftcardTransaction;
use Piggy\Api\Models\Loyalty\Receptions\CreditReception;
use Piggy\Api\Resources\BaseResource;

class CreditReceptionsResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/oauth/clients/credit-receptions';

    /**
     * @param  mixed[]|null  $attributes
     *
     * @throws PiggyRequestException
     */
    public function create(
        string $contactUuid,
        string $shopUuid,
        ?float $unitValue = null,
        ?int $credits = null,
        ?string $contactIdentifierValue = null,
        ?string $unitName = null,
        ?string $posTransactionUuid = null,
        ?array $attributes = []
    ): CreditReception {
        $data = [
            'contact_uuid' => $contactUuid,
            'shop_uuid' => $shopUuid,
            'credits' => $credits,
            'unit_value' => $unitValue,
            'contact_identifier_value' => $contactIdentifierValue,
            'pos_transaction_id' => $posTransactionUuid,
            'unit_name' => $unitName,

        ] + $attributes;

        $response = $this->client->post($this->resourceUri, $data);

        $mapper = new CreditReceptionMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @throws PiggyRequestException
     */
    public function calculate(string $shopUuid, float $unitValue, ?string $contactUuid = null): int
    {
        $data = [
            'shop_uuid' => $shopUuid,
            'unit_value' => $unitValue,
        ];

        if ($contactUuid != null) {
            $data['contact_uuid'] = $contactUuid;
        }

        $response = $this->client->get($this->resourceUri.'/calculate', $data);

        return (int) $response->getData()->credits;
    }

    /**
     * @param string $giftcardTransactionUuid
     * @return GiftcardTransaction
     * @throws PiggyRequestException
     */
    public function correct(string $giftcardTransactionUuid): GiftcardTransaction
    {
        $response = $this->client->post("$this->resourceUri/$giftcardTransactionUuid/correct", []);

        $mapper = new GiftcardTransactionMapper();

        return $mapper->map($response->getData());
    }

    /**
     * @param string $creditReceptionUuid
     * @return CreditReception
     * @throws PiggyRequestException
     */
    public function reverse(string $creditReceptionUuid): CreditReception
    {
        $response = $this->client->post("$this->resourceUri/$creditReceptionUuid/reverse", []);

        $mapper = new CreditReceptionMapper();

        return $mapper->map($response->getData());
    }
}