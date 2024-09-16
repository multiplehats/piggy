<?php

namespace Piggy\Api\Mappers\Contacts;

use Piggy\Api\Mappers\Loyalty\CreditBalanceMapper;
use Piggy\Api\Mappers\Prepaid\PrepaidBalanceMapper;
use Piggy\Api\Models\Contacts\Contact;
use stdClass;

class ContactMapper
{
    public function map(stdClass $data): Contact
    {
        $prepaidBalance = null;
        if (property_exists($data, 'prepaid_balance')) {
            $prepaidBalanceMapper = new PrepaidBalanceMapper();
            $prepaidBalance = $prepaidBalanceMapper->map($data->prepaid_balance);
        }

        $creditBalance = null;
        if (property_exists($data, 'credit_balance')) {
            $creditBalanceMapper = new CreditBalanceMapper();
            $creditBalance = $creditBalanceMapper->map($data->credit_balance);
        }

        $attributes = null;
        if (property_exists($data, 'attributes')) {
            $attributesMapper = new ContactAttributesMapper();
            $attributes = $attributesMapper->map($data->attributes);
        }

        $currentValues = [];
        if (property_exists($data, 'current_values')) {
            $currentValues = get_object_vars($data->current_values);
        }

        $subscriptionMapper = new SubscriptionsMapper();

        if (isset($data->subscriptions)) {
            $subscriptions = $subscriptionMapper->map($data->subscriptions);
        } else {
            $subscriptions = null;
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
