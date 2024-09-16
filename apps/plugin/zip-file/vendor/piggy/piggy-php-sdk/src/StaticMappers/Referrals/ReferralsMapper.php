<?php

namespace Piggy\Api\StaticMappers\Referrals;

class ReferralsMapper
{
    public static function map($data): array
    {
        $referrals = [];
        foreach ($data as $item) {
            $referrals[] = ReferralMapper::map($item);
        }

        return $referrals;
    }
}
