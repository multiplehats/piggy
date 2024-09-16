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

    /**
     * Get the item response
     *
     * @param \WC_Product $product_object
     * @return array
     */
    public function get_item_response($product_object) {
        $formatted_name = $product_object->get_formatted_name();
        $managing_stock = $product_object->managing_stock();

        if ( ! wc_products_array_filter_readable( $product_object ) ) {
            return [];
        }

        return [
            'id' => $product_object->get_id(),
            'title' => $formatted_name,
        ];
    }
}