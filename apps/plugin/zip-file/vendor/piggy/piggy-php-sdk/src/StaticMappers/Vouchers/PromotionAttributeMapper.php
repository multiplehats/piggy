<?php

namespace Piggy\Api\StaticMappers\Vouchers;

use Piggy\Api\Models\Vouchers\PromotionAttribute;

class PromotionAttributeMapper
{
    public static function map($data): PromotionAttribute
    {
        $options = [];

        if (isset($data->options)) {
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
