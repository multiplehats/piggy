<?php

namespace Piggy\Api\Mappers\Vouchers;

use Piggy\Api\Models\Vouchers\PromotionAttribute;
use stdClass;

class PromotionAttributeMapper
{
    public function map(stdClass $data): PromotionAttribute
    {
        $options = [];

        if (property_exists($data, 'options') && $data->options != null) {
            foreach ($data->options as $item) {
                $options[] = get_object_vars($item);
            }
        }

        return new PromotionAttribute(
            $data->name,
            $data->description,
            $data->label,
            $data->type,
            $options,
            $data->id ?? null,
            $data->placeholder ?? null
        );
    }
}
