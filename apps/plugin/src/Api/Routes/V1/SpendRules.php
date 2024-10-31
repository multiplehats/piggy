<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Api\Routes\V1\Admin\Middleware;

/**
 * Shops class.
 *
 * @internal
 */
class SpendRules extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'spend-rules';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'spend-rules';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/spend-rules';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [
					'settings' => [
						'description' => __( 'Spend rules', 'leat' ),
						'type'        => 'object',
					],
				],
			],
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'description' => __( 'Spend rule ID', 'leat' ),
						'type'        => 'string',
					],
					'status' => [
						'description' => __( 'Spend rule status', 'leat' ),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Saves spend rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$data = array(
			'id' => $request->get_param( 'id' ),
			'status' => $request->get_param( 'status' ),
			'label' => $request->get_param( 'label' ),
			'title' => $request->get_param( 'title' ),
			'type' => $request->get_param( 'type' ),
			'startsAt' => $request->get_param( 'startsAt' ),
			'expiresAt' => $request->get_param( 'expiresAt' ),
			'completed' => $request->get_param( 'completed' ),
			'instructions' => $request->get_param( 'instructions' ),
			'description' => $request->get_param( 'description' ),
			'fulfillment' => $request->get_param( 'fulfillment' ),
			'discountValue' => $request->get_param( 'discountValue' ),
			'discountType' => $request->get_param( 'discountType' ),
			'minimumPurchaseAmount' => $request->get_param( 'minimumPurchaseAmount' ),
			'selectedProducts' => $request->get_param( 'selectedProducts' ),
		);

		$post_data = array(
			'post_type' => 'leat_spend_rule',
			'post_title' => $data['title'],
			'post_status' => $data['status'],
			'meta_input' => array(
				'_leat_spend_rule_type' => $data['type'],
				'_leat_spend_rule_label' => $data['label'],
				'_leat_spend_rule_starts_at' => $data['startsAt'],
				'_leat_spend_rule_expires_at' => $data['expiresAt'],
				'_leat_spend_rule_completed' => $data['completed'],
				'_leat_spend_rule_instructions' => $data['instructions'],
				'_leat_spend_rule_description' => $data['description'],
				'_leat_spend_rule_fulfillment' => $data['fulfillment'],
				'_leat_spend_rule_discount_value' => $data['discountValue'],
				'_leat_spend_rule_discount_type' => $data['discountType'],
				'_leat_spend_rule_minimum_purchase_amount' => $data['minimumPurchaseAmount'],
				'_leat_spend_rule_selected_products' => $data['selectedProducts'],
			)
		);

		if ( ! empty( $data['id'] ) ) {
			$post_data['ID'] = $data['id'];
			$post_id = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return new \WP_Error( 'post_save_failed', __( 'Failed to save spend rule', 'leat' ), array( 'status' => 500 ) );
		}

		$response = $this->prepare_item_for_response( get_post( $post_id ), $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Get spend rules or a specific spend rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$prepared_args = array(
			'post_type' => 'leat_spend_rule',
			'posts_per_page' => -1,
			'post_status' => $request->get_param( 'status' ) ? explode( ',', $request->get_param( 'status' ) ) : array('publish'),
		);

		$id = $request->get_param( 'id' );

		if($id) {
			// Get a specific post id
			$prepared_args['p'] = $id;
		}

		$query = new \WP_Query();
		$query_result = $query->query( $prepared_args );

		$response_objects = array();

		foreach ( $query_result as $post ) {
			$data               = $this->prepare_item_for_response( $post, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $response_objects );

		return $response;
	}
}
