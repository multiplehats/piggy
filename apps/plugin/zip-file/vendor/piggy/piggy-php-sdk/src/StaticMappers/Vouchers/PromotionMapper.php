<?php

namespace Piggy\Api\StaticMappers\Vouchers;

use Piggy\Api\Models\Vouchers\Promotion;

class PromotionMapper
{
    public static function map($data): Promotion
    {
        return new Promotion(
            $data->uuid,
            $data->name,
            $data->description,
            $data->voucher_limit ?? null,
            $data->limit_per_contact ?? null,
            $data->expiration_duration ?? null,
            isset($data->attributes) ? get_object_vars($data->attributes) : []
        );
    }
}
