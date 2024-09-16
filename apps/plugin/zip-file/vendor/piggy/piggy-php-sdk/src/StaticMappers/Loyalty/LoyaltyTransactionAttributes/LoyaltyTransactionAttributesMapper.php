<?php

namespace Piggy\Api\StaticMappers\Loyalty\LoyaltyTransactionAttributes;

class LoyaltyTransactionAttributesMapper
{
    public static function map($data): array
    {
        $loyaltyTransactionAttributes = [];

        foreach ($data as $item) {
            $loyaltyTransactionAttributes[] = LoyaltyTransactionAttributeMapper::map($item);
        }

        return $loyaltyTransactionAttributes;
    }
}
