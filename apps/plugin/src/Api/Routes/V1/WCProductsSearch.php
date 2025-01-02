<?php

namespace  Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Admin\Middleware;
use WP_REST_Request;

class WCProductsSearch extends AbstractRoute {

	const IDENTIFIER  = 'wc-products';
	const SCHEMA_TYPE = 'wc-products';

	public function get_path() {
		return '/wc-products';
	}

	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [
					'term'    => [
						'description' => __( 'Search term', 'leat-crm' ),
						'type'        => 'string',
					],
					'limit'   => [
						'description' => __( 'Limit', 'leat-crm' ),
						'type'        => 'integer',
					],
					'include' => [
						'description' => __( 'Include IDs', 'leat-crm' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'integer',
						],
					],
					'exclude' => [
						'description' => __( 'Exclude IDs', 'leat-crm' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'integer',
						],
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	protected function get_route_response( WP_REST_Request $request ) {
		$ids = $request->get_param( 'ids' );
		if ( empty( $ids ) ) {
			return rest_ensure_response( [] );
		}

		$ids              = array_map( 'absint', explode( ',', $ids ) );
		$response_objects = [];

		foreach ( $ids as $id ) {
			$product_object = wc_get_product( $id );

			if ( ! wc_products_array_filter_readable( $product_object ) ) {
				continue;
			}

			$data               = $this->prepare_item_for_response( $product_object, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $response_objects );
	}

	protected function get_route_post_response( \WP_REST_Request $request ) {
		$term = $request->get_param( 'term' );
		if ( empty( $term ) ) {
			return rest_ensure_response( [] );
		}

		$limit       = $request->get_param( 'limit' ) ? absint( $request->get_param( 'limit' ) ) : absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
		$include_ids = $request->get_param( 'include' ) ? array_map( 'absint', (array) $request->get_param( 'include' ) ) : [];
		$exclude_ids = $request->get_param( 'exclude' ) ? array_map( 'absint', (array) $request->get_param( 'exclude' ) ) : [];

		$exclude_types = [];
		if ( $request->get_param( 'exclude_type' ) ) {
			$exclude_types = $request->get_param( 'exclude_type' );
			if ( ! is_array( $exclude_types ) ) {
				$exclude_types = explode( ',', $exclude_types );
			}
			$exclude_types = array_map( 'strtolower', array_map( 'trim', $exclude_types ) );
			$exclude_types = array_intersect(
				array_merge( [ 'variation' ], array_keys( wc_get_product_types() ) ),
				$exclude_types
			);
		}

		$data_store = \WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', true, false, $limit );

		if ( ! empty( $include_ids ) ) {
			$ids = array_intersect( $ids, $include_ids );
		}
		if ( ! empty( $exclude_ids ) ) {
			$ids = array_diff( $ids, $exclude_ids );
		}

		$response_objects = [];

		foreach ( $ids as $id ) {
			$product_object = wc_get_product( $id );

			if ( ! wc_products_array_filter_readable( $product_object ) ) {
				continue;
			}

			if ( in_array( $product_object->get_type(), $exclude_types, true ) ) {
				continue;
			}

			$data               = $this->prepare_item_for_response( $product_object, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		$response_objects = array_filter(
			$response_objects,
			function ( $item ) {
				return ! empty( $item );
			}
			);

		return rest_ensure_response( $response_objects );
	}
}
