<?php

namespace Piggy\Api\Mappers\Vouchers;

use Piggy\Api\Models\Vouchers\PromotionAttribute;
use stdClass;

class PromotionAttributesMapper
{
    /**
     * @param  stdClass[]  $data
     * @return PromotionAttribute[]
     */
    public function map(array $data): array
    {
        $promotionAttributeMapper = new PromotionAttributeMapper();

        $attributes = [];
        foreach ($data as $item) {
            $attributes[] = $promotionAttributeMapper->map($item);
        }

        return $attributes;
    }
}
