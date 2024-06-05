<?php

namespace PiggyWP\Api\Routes\V1\Admin;

use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Api\Routes\V1\Admin\Middleware;

/**
 * Shops class.
 *
 * @internal
 */
class EarnRules extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'earn-rules';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'earn-rules';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/earn-rules';
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
						'description' => __( 'Earn rules', 'piggy' ),
						'type'        => 'object',
					],
				],
			],
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ Middleware::class, 'is_authorized' ],
				'args'                => [
					'id' => [
						'description' => __( 'Earn rule ID', 'piggy' ),
						'type'        => 'string',
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Saves earn rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$data = array(
			'id' => $request->get_param( 'id' ),
			'title' => $request->get_param( 'title' ),
			'description' => $request->get_param( 'description' ),
			'type' => $request->get_param( 'type' ),
			'piggyTierUuids' => $request->get_param( 'piggyTierUuids' ),
			'startsAt' => $request->get_param( 'startsAt' ),
			'expiresAt' => $request->get_param( 'expiresAt' ),
			'completed' => $request->get_param( 'completed' ),
			'points' => $request->get_param( 'points' ),
			'socialNetworkUrl' => $request->get_param( 'socialNetworkUrl' ),
			'socialMessage' => $request->get_param( 'socialMessage' ),
			'excludedCollectionIds' => $request->get_param( 'excludedCollectionIds' ),
			'excludedProductIds' => $request->get_param( 'excludedProductIds' ),
			'minOrderSubtotalCents' => $request->get_param( 'minOrderSubtotalCents' ),
		);

		$post_data = array(
			'post_type' => 'piggy_earn_rule',
			'post_title' => $data['title'],
			'post_content' => $data['description'],
			'post_status' => 'publish',
			'meta_input' => array(
				'_piggy_earn_rule_description' => $data['description'],
				'_piggy_earn_rule_type' => $data['type'],
				'_piggy_earn_rule_piggy_tier_uuids' => $data['piggyTierUuids'],
				'_piggy_earn_rule_starts_at' => $data['startsAt'],
				'_piggy_earn_rule_expires_at' => $data['expiresAt'],
				'_piggy_earn_rule_completed' => $data['completed'],
				'_piggy_earn_rule_points' => $data['points'],
				'_piggy_earn_rule_social_network_url' => $data['socialNetworkUrl'],
				'_piggy_earn_rule_social_message' => $data['socialMessage'],
				'_piggy_earn_rule_excluded_collection_ids' => $data['excludedCollectionIds'],
				'_piggy_earn_rule_excluded_product_ids' => $data['excludedProductIds'],
				'_piggy_earn_rule_min_order_subtotal_cents' => $data['minOrderSubtotalCents'],
			)
		);

		if ( ! empty( $data['id'] ) ) {
			$post_data['ID'] = $data['id'];
			$post_id = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return new \WP_Error( 'post_save_failed', __( 'Failed to save earn rule', 'piggy' ), array( 'status' => 500 ) );
		}

		$response = $this->prepare_item_for_response( get_post( $post_id ), $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Get earn rules or a specific earn rule
	 *
	 * @param  \WP_REST_Request $request Request object.
	 *
	 * @return bool|string|\WP_Error|\WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$prepared_args = array(
			'post_type' => 'piggy_earn_rule',
			'posts_per_page' => -1,
			'post_status' => array('publish', 'draft'),
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
