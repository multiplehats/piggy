<?php

namespace Piggy\Api\Mappers\Vouchers;

use Piggy\Api\Models\Vouchers\Promotion;
use stdClass;

class PromotionsMapper
{
    /**
     * @param  stdClass[]  $data
     * @return Promotion[]
     */
    public function map(array $data): array
    {
        $mapper = new PromotionMapper();

        $promotions = [];

        foreach ($data as $item) {
            $promotions[] = $mapper->map($item);
        }

        return $promotions;
    }
}
