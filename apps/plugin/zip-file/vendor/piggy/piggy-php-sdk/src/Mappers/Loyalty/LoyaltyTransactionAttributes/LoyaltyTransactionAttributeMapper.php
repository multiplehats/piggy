<?php

namespace Piggy\Api\Mappers\Loyalty\LoyaltyTransactionAttributes;

use Piggy\Api\Models\Loyalty\Transactions\LoyaltyTransactionAttribute;
use stdClass;

class LoyaltyTransactionAttributeMapper
{
    public function map(stdClass $data): LoyaltyTransactionAttribute
    {
        $options = [];

        if (! empty($data->options)) {
            foreach ($data->options as $item) {
                $options[] = get_object_vars($item);
            }
        }

        return new LoyaltyTransactionAttribute(
            $data->name,
            $data->label,
            $data->type,
            $data->field_type,
            $data->placeholder,
            $options,
            $data->is_piggy_defined,
            $data->is_soft_read_only,
            $data->is_hard_read_only
        );
    }
}
