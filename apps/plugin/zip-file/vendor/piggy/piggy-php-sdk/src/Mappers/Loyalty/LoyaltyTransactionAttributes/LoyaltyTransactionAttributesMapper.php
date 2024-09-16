<?php

namespace Piggy\Api\Mappers\Loyalty\LoyaltyTransactionAttributes;

use Piggy\Api\Models\Loyalty\Transactions\LoyaltyTransactionAttribute;
use stdClass;

class LoyaltyTransactionAttributesMapper
{
    /**
     * @param  stdClass[]  $data
     * @return LoyaltyTransactionAttribute[]
     */
    public function map(array $data): array
    {
        $mapper = new LoyaltyTransactionAttributeMapper();

        $LoyaltyTransactionAttributes = [];
        foreach ($data as $item) {
            $LoyaltyTransactionAttributes[] = $mapper->map($item);
        }

        return $LoyaltyTransactionAttributes;
    }
}
