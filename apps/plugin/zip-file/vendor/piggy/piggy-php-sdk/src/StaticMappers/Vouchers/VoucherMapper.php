<?php

namespace Piggy\Api\StaticMappers\Vouchers;

use Piggy\Api\Models\Vouchers\Voucher;
use Piggy\Api\StaticMappers\BaseMapper;
use Piggy\Api\StaticMappers\Contacts\ContactMapper;

class VoucherMapper extends BaseMapper
{
    public static function map($data): Voucher
    {
        if (isset($data->promotion)) {
            $promotion = PromotionMapper::map($data->promotion);
        }

        if (isset($data->contact)) {
            $contact = ContactMapper::map($data->contact);
        }

        return new Voucher(
            $data->uuid,
            $data->status,
            $data->code ?? null,
            $data->name ?? null,
            $data->description ?? null,
            $promotion ?? null,
            $contact ?? null,
            isset($data->redeemed_at) ? self::parseDate($data->redeemed_at) : null,
            $data->is_redeemed ?? null,
            isset($data->activation_date) ? self::parseDate($data->activation_date) : null,
            isset($data->expiration_date) ? self::parseDate($data->expiration_date) : null,
            isset($data->attributes) ? get_object_vars($data->attributes) : []
        );
    }
}
