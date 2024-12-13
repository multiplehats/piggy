<?php

namespace Leat\Api\Routes\V1;

use Leat\Api\Exceptions\RouteException;
use Leat\Api\Routes\V1\AbstractRoute;
use Leat\Domain\Services\VoucherSync;

/**
 * Coupons class.
 *
 * @internal
 */
class Vouchers extends AbstractRoute {
    /**
     * The route identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'vouchers';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    const SCHEMA_TYPE = 'vouchers';

    /**
     * Get the path of this REST route.
     *
     * @return string
     */
    public function get_path() {
        return '/vouchers';
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
                'args'                => [
                    'userId' => [
                        'type'              => 'integer',
                        'validate_callback' => function($param) {
                            return is_numeric($param) && $param > 0;
                        },
                        'sanitize_callback' => 'absint',
                    ],
                ],
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
        $user_id = $request->get_param('userId');

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            throw new RouteException( 'no_user_id', 'User ID is required', 400 );
        }

        $voucher_sync_service = new VoucherSync();
        $vouchers = $voucher_sync_service->get_vouchers_for_user( $user_id );

        $response_objects = array();

		foreach ( $vouchers as $voucher ) {
			$data               = $this->prepare_item_for_response( $voucher, $request );
			$response_objects[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $response_objects );

		return $response;
    }
}