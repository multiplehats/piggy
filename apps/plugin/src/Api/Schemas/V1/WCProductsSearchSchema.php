<?php

namespace PiggyWP\Api\Schemas\V1;

use PiggyWP\Api\Schemas\V1\AbstractSchema;

class WCProductsSearchSchema extends AbstractSchema {
    protected $title = 'wc-products';
    const IDENTIFIER = 'wc-products';

    public function get_properties() {
        return [
            'id' => [
                'description' => __('Unique identifier for the product or variation', 'piggy'),
                'type'        => 'integer',
            ],
            'title' => [
                'description' => __('Title of the product or variation', 'piggy'),
                'type'        => 'string',
            ],
        ];
    }

    public function get_item_response($item) {
        return [
            'id' => $item['id'],
            'title' => $item['title'],
        ];
    }
}