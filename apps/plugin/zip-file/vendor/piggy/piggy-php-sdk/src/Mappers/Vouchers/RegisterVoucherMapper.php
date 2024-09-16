<?php

namespace Piggy\Api\Mappers\Vouchers;

use Piggy\Api\Mappers\BaseMapper;
use Piggy\Api\Mappers\Contacts\ContactMapper;
use Piggy\Api\Models\Vouchers\Voucher;
use stdClass;

class RegisterVoucherMapper extends BaseMapper
{
    public function map(stdClass $data): Voucher
    {
        if (isset($data->promotion)) {
            $promotionMapper = new PromotionMapper();
            $promotion = $promotionMapper->map($data->promotion);
        }

        if (isset($data->contact)) {
            $contactMapper = new ContactMapper();
            $contact = $contactMapper->map($data->contact);
        }

        if (isset($data->attributes) && is_object($data->attributes)) {
            $attributes = get_object_vars($data->attributes);
        }

        return new Voucher(
            $data->uuid,
            $data->status,
            $data->code ?? null,
            $data->name ?? null,
            $data->description ?? null,
            $promotion ?? null,
            $contact ?? null,
            isset($data->redeemed_at) ? $this->parseDate($data->redeemed_at) : null,
            $data->is_redeemed ?? null,
            isset($data->activation_date) ? $this->parseDate($data->activation_date) : null,
            isset($data->expiration_date) ? $this->parseDate($data->expiration_date) : null,
            $attributes ?? []
        );
    }
}
