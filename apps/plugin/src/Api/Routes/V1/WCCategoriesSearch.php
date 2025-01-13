<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Middleware;
use WP_REST_Request;

class WCCategoriesSearch extends AbstractRoute {

	const IDENTIFIER  = 'wc-categories';
	const SCHEMA_TYPE = 'wc-categories';

	public function get_path() {
		return '/wc-categories';
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
			$term = get_term( $id, 'product_cat' );

			if ( is_wp_error( $term ) || ! $term ) {
				continue;
			}

			$data               = $this->prepare_item_for_response( $term, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $response_objects );
	}

	protected function get_route_post_response( \WP_REST_Request $request ) {
		$term = $request->get_param( 'term' );
		if ( empty( $term ) ) {
			return rest_ensure_response( [] );
		}

		$args = [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'search'     => $term,
			'number'     => $request->get_param( 'limit' ) ?: 30,
		];

		if ( $request->get_param( 'include' ) ) {
			$args['include'] = array_map( 'absint', (array) $request->get_param( 'include' ) );
		}

		if ( $request->get_param( 'exclude' ) ) {
			$args['exclude'] = array_map( 'absint', (array) $request->get_param( 'exclude' ) );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || ! $terms ) {
			return rest_ensure_response( [] );
		}

		$response_objects = [];
		foreach ( $terms as $term ) {
			$data               = $this->prepare_item_for_response( $term, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $response_objects );
	}
}
