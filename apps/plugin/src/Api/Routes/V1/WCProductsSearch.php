<?php

namespace    PiggyWP\Api\Routes\V1;

use    PiggyWP\Api\Routes\V1\AbstractRoute;
use    PiggyWP\Api\Routes\V1\Admin\Middleware;

class WCProductsSearch extends AbstractRoute
{
    const IDENTIFIER = 'wc-products';
    const SCHEMA_TYPE = 'wc-products';

    public    function get_path()
    {
        return '/wc-products';
    }

    public function get_args()
    {
        return [
            [
                'methods'                => \WP_REST_Server::READABLE,
                'callback'                => [$this, 'get_response'],
                'permission_callback'    => [Middleware::class, 'is_authorized'],
                'args'                    => [],
            ],
            'schema'        => [$this->schema, 'get_public_item_schema'],
            'allow_batch'    => ['v1' => true],
        ];
    }

    protected function get_route_response(\WP_REST_Request $request)
    {
        $term = $request->get_param('term');
        $exclude = $request->get_param('exclude');

        if (!$term) {
            return rest_ensure_response([]);
        }

        $data_store = \WC_Data_Store::load('product');
        $ids = $data_store->search_products($term, '', true, false, 30);

        if (!empty($exclude)) {
            $ids = array_diff($ids, [$exclude]);
        }

        $products = [];

        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if ($product) {
                $products[$id] = rawurldecode($product->get_formatted_name());
            }
        }

        return rest_ensure_response($products);
    }
}
