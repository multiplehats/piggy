<?php

namespace Piggy\Api\Resources\Register;

use Piggy\Api\Exceptions\PiggyRequestException;
use Piggy\Api\Mappers\Prepaid\PrepaidTransactionMapper;
use Piggy\Api\Models\Prepaid\PrepaidTransaction;
use Piggy\Api\Resources\BaseResource;

class PrepaidTransactionResource extends BaseResource
{
    /**
     * @var string
     */
    protected $resourceUri = '/api/v3/register/prepaid-transactions';

    /**
     * @throws PiggyRequestException
     */
    public function create(string $contactUuid, int $amountInCents, ?string $contactIdentifierValue = null): PrepaidTransaction
    {
        $response = $this->client->post("$this->resourceUri", [
            'contact_uuid' => $contactUuid,
            'amount_in_cents' => $amountInCents,
            'contact_identifier_value' => $contactIdentifierValue,
        ]);

        $mapper = new PrepaidTransactionMapper();

        return $mapper->map($response->getData());
    }
}
