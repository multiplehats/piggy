<?php

namespace Piggy\Api\StaticMappers\Contacts;

use Piggy\Api\Models\Contacts\Contact;
use Piggy\Api\StaticMappers\Loyalty\CreditBalanceMapper;
use Piggy\Api\StaticMappers\Prepaid\PrepaidBalanceMapper;
use stdClass;

class ContactMapper
{
    public static function map(stdClass $data): Contact
    {
        $prepaidBalance = null;
        if (isset($data->prepaid_balance)) {
            $prepaidBalance = PrepaidBalanceMapper::map($data->prepaid_balance);
        }

        $creditBalance = null;
        if (isset($data->credit_balance)) {
            $creditBalance = CreditBalanceMapper::map($data->credit_balance);
        }

        $attributes = null;
        if (isset($data->attributes)) {
            $attributes = ContactAttributesMapper::map($data->attributes);
        }

        $subscriptions = [];
        if (isset($data->subscriptions)) {
            $subscriptions = SubscriptionsMapper::map($data->subscriptions);
        }

        $currentValues = [];
        if (isset($data->current_values)) {
            $currentValues = get_object_vars($data->current_values);
        }

        return new Contact(
            $data->uuid,
            $data->email ?? '',
            $prepaidBalance,
            $creditBalance,
            $attributes,
            $subscriptions,
            $currentValues
        );
    }
}
