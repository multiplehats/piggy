<?php

namespace PiggyWP\Api\Routes\V1;

use PiggyWP\Api\Exceptions\RouteException;
use PiggyWP\Api\Routes\V1\AbstractRoute;
use PiggyWP\Domain\Services\SpendRules;

/**
 * Coupons class.
 *
 * @internal
 */
class Coupons extends AbstractRoute {
    /**
     * The route identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'coupons';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    const SCHEMA_TYPE = 'coupons';

    /**
     * Get the path of this REST route.
     *
     * @return string
     */
    public function get_path() {
        return '/coupons';
    }

    /**
     * Get method arguments for this REST route.
     *
     * @return array An array of endpoints.
     */
    public function get_args() {
        return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
			],
            'schema' => [ $this->schema, 'get_public_item_schema' ],
        ];
    }

    /**
     * Get user coupons
     *
     * @param  \WP_REST_Request $request Request object.
     *
     * @return \WP_REST_Response
     */
    public function get_route_response( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            throw new RouteException( 'no_user_id', 'User ID is required', 400 );
        }

        $spend_rules_service = new SpendRules();
        $coupons = $spend_rules_service->get_coupons_by_user_id( $user_id );

        $response_objects = array();

		foreach ( $coupons as $coupon ) {
			$data               = $this->prepare_item_for_response( $coupon, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $response_objects );

		return $response;
    }
}